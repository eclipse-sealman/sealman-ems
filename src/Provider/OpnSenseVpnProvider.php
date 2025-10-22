<?php

// Copyright (c) 2025 Contributors to the Eclipse Foundation.
//
// See the NOTICE file(s) distributed with this work for additional
// information regarding copyright ownership.
//
// This program and the accompanying materials are made available under the
// terms of the Apache License, Version 2.0 which is available at
// https://www.apache.org/licenses/LICENSE-2.0
//
// SPDX-License-Identifier: Apache-2.0

declare(strict_types=1);

namespace App\Provider;

use App\Exception\ProviderException;
use App\Model\VpnConnectedClientsModel;
use App\Provider\Enum\Protocol;
use App\Provider\Interface\VpnProviderInterface;
use App\Provider\Model\FirewallRuleConfiguration;
use App\Provider\Model\FirewallRuleConfigurationCollection;
use App\Provider\Model\VpnConnectedClientsCollection;
use App\Provider\Model\VpnCscConfiguration;
use App\Trait\LogsCollectorTrait;
use Carve\ApiBundle\Helper\Arr;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpnSenseVpnProvider implements VpnProviderInterface
{
    use LogsCollectorTrait;

    public const ENDPOINT_CONNECTED_CLIENTS = 'vpnsecuritysuite-opnsense/connected-clients.php';
    public const ENDPOINT_CRL = 'vpnsecuritysuite-opnsense/crl.php';
    public const ENDPOINT_SERVERS_DATA = 'vpnsecuritysuite-opnsense/servers_data.php';
    public const ENDPOINT_CSC_GET_LIST = 'vpnsecuritysuite-opnsense/get-csc-list.php';
    public const ENDPOINT_CSC_DELETE = 'vpnsecuritysuite-opnsense/delete-csc.php';
    public const ENDPOINT_CSC_ADD = 'vpnsecuritysuite-opnsense/add-csc.php';
    public const ENDPOINT_FIREWALL_RULES = 'vpnsecuritysuite-opnsense/firewall-rules.php';
    public const ENDPOINT_FIREWALL_DELETE_RULE = 'vpnsecuritysuite-opnsense/delete-firewall-rule.php';
    public const ENDPOINT_FIREWALL_ADD_RULE = 'vpnsecuritysuite-opnsense/add-firewall-rule.php';

    protected ProviderHttpClient $httpClient;

    /**
     * @param string  $baseUri                    OpnSense base URI
     * @param int     $opnsenseTimeout            OpnSense request timeout
     * @param bool    $verifyServerSslCertificate Should verify server SSL certificate?
     * @param ?string $user                       Basic Auth user
     * @param ?string $password                   Basic Auth password
     */
    public function __construct(
        HttpClientInterface $httpClient,
        string $baseUri,
        int $opnsenseTimeout,
        bool $verifyServerSslCertificate,
        ?string $user = null,
        ?string $password = null,
    ) {
        $this->configureHttpClient($httpClient, $baseUri, $opnsenseTimeout, $verifyServerSslCertificate, $user, $password);
    }

    protected function configureHttpClient(HttpClientInterface $httpClient, string $baseUri, int $opnsenseTimeout, bool $verifyServerSslCertificate, ?string $user = null, ?string $password = null)
    {
        $options = [
            'verify_peer' => $verifyServerSslCertificate,
            'verify_host' => $verifyServerSslCertificate,
            'base_uri' => $baseUri,
            'max_duration' => $opnsenseTimeout,
            'timeout' => $opnsenseTimeout,
        ];

        if (null !== $user && null !== $password) {
            $options['auth_basic'] = $user.':'.$password;
        }

        // withOptions() returns a new instance of the client with new default options
        $httpClient = $httpClient->withOptions($options);

        $this->httpClient = new ProviderHttpClient($this, $httpClient, 'OPNsense', true);
    }

    public function getVpnConnectedClients(): VpnConnectedClientsCollection
    {
        $connections = $this->httpClient->post(self::ENDPOINT_CONNECTED_CLIENTS);

        $vpnConnectedClientsCollection = new VpnConnectedClientsCollection();

        foreach ($connections as $definedConnection) {
            if (!isset($definedConnection['conns'])) {
                continue;
            }

            foreach ($definedConnection['conns'] as $connection) {
                // We assume 'common_name' = connected User, Device certificate subject
                // We assume 'virtual_addr' = connected User, Device Virtual IP Address
                // We assume one connection per certificate subject

                $vpnConnectedClient = new VpnConnectedClientsModel();

                if (isset($connection['bytes_recv']) && is_numeric($connection['bytes_recv'])) {
                    $vpnConnectedClient->setBytesReceived(intval($connection['bytes_recv']));
                }
                if (isset($connection['bytes_sent']) && is_numeric($connection['bytes_sent'])) {
                    $vpnConnectedClient->setBytesSent(intval($connection['bytes_sent']));
                }

                if (
                    isset($connection['common_name']) &&
                    is_string($connection['common_name']) &&
                    !empty($connection['common_name']) &&
                    isset($connection['virtual_addr']) &&
                    is_string($connection['virtual_addr']) &&
                    !empty($connection['virtual_addr'])
                ) {
                    // if common name and virtual IP address are not provided this is not counted as valid connection
                    $vpnConnectedClient->setCommonName($connection['common_name']);
                    $vpnConnectedClient->setVpnIp($connection['virtual_addr']);

                    $vpnConnectedClientsCollection->add($vpnConnectedClient);
                }
            }
        }

        return $vpnConnectedClientsCollection;
    }

    public function updateVpnServerCrl(string $serverDescription, string $crlContentPem): void
    {
        $crlReferenceId = $this->getVpnServerCrlReferenceId($serverDescription);

        // Update CRL using $crlReferenceId and $serverDescription
        $data = [
            'id' => $crlReferenceId,
            'descr' => $serverDescription,
            'crlmethod' => 'existing',
            'crltext' => $crlContentPem,
        ];
        $crlUpdateRequestResult = $this->httpClient->post(self::ENDPOINT_CRL, $data);

        // Check result of CRL update request
        $requestStatus = Arr::get($crlUpdateRequestResult, 'result.status');

        if (null === $requestStatus) {
            throw new ProviderException($this->addLogError('log.opnSenseVpnProvider.updateCrl.invalidResponse', ['serverDescription' => $serverDescription]));
        }

        if ('OK' !== $requestStatus) {
            $inputErrors = Arr::get($crlUpdateRequestResult, 'result.input_errors');

            $errorsAsString = (null !== $inputErrors) ? implode(', ', $inputErrors) : 'N/A';

            throw new ProviderException($this->addLogError('log.opnSenseVpnProvider.updateCrl.errors', ['serverDescription' => $serverDescription, 'errors' => $errorsAsString]));
        }
    }

    protected function getVpnServerCrlReferenceId(string $serverDescription): string|int
    {
        $crlListArray = $this->getVpnServerCrls($serverDescription);

        /**
         * $crlListArray expected structure:
         * [
         *  [
         *      'refid' => 'Server CRL Reference ID',
         *      'descr' => 'VPN server description',
         *  ]
         * ].
         */
        $crlItem = Arr::first($crlListArray, fn ($crlItem) => Arr::get($crlItem, 'descr') === $serverDescription);
        $crlReferenceId = Arr::get($crlItem, 'refid');

        if (null === $crlReferenceId) {
            throw new ProviderException($this->addLogError('log.opnSenseVpnProvider.getCrl.crlNotExisting', ['serverDescription' => $serverDescription]));
        }

        return $crlReferenceId;
    }

    protected function getVpnServerCrls(string $serverDescription): array
    {
        // Get current CRL list and find $crlReferenceId
        $crlGetRequestResult = $this->httpClient->post(self::ENDPOINT_CRL);

        $crlListArray = Arr::get($crlGetRequestResult, 'result');

        if (!is_array($crlListArray)) {
            throw new ProviderException($this->addLogError('log.opnSenseVpnProvider.getCrl.invalidResponse', ['serverDescription' => $serverDescription]));
        }

        /*
         * $crlListArray expected structure:
         * [
         *  [
         *      'refid' => 'Server CRL Reference ID',
         *      'descr' => 'VPN server description',
         *      'text' => 'CRL content',
         *  ]
         * ].
         */
        return $crlListArray;
    }

    public function getVpnServerNameByDescription(string $serverDescription): string
    {
        // Get current VPN Server list
        $serverDataRequestResult = $this->httpClient->post(self::ENDPOINT_SERVERS_DATA);

        $vpnServerListArray = Arr::get($serverDataRequestResult, 'result');
        if (!is_array($vpnServerListArray)) {
            throw new ProviderException($this->addLogError('log.opnSenseVpnProvider.getVpnServer.invalidResponse', ['serverDescription' => $serverDescription]));
        }

        /**
         * $vpnServerListArray expected structure:
         * [
         *  'vpnServerName' => [
         *      'description' => 'VPN server description'
         *  ]
         * ].
         */
        $serverName = Arr::firstKey($vpnServerListArray, fn ($vpnServer) => Arr::get($vpnServer, 'description') === $serverDescription);

        if (null === $serverName) {
            throw new ProviderException($this->addLogError('log.opnSenseVpnProvider.getVpnServer.serverMissing', ['serverDescription' => $serverDescription]));
        }

        return (string) $serverName;
    }

    public function isCscInVpnServer(string $cscCommonName): bool
    {
        // Get OpnSense CSC list (for all VPN servers) and find one by commonName
        $cscList = $this->httpClient->post(self::ENDPOINT_CSC_GET_LIST);

        /**
         * $cscList expected structure:
         * [
         *  [
         *      'common_name' => 'CSC common name'
         *  ]
         * ].
         */
        $cscItemKey = Arr::firstKey($cscList, fn ($cscItem) => Arr::get($cscItem, 'common_name') === $cscCommonName);

        if (null === $cscItemKey) {
            return false;
        }

        return true;
    }

    public function deleteCscInVpnServer(string $cscCommonName): void
    {
        // Remove OpnSense CSC  by commonName
        $data = ['common_name' => $cscCommonName];
        $cscRemoveResponse = $this->httpClient->post(self::ENDPOINT_CSC_DELETE, $data);

        if (!array_key_exists('status', $cscRemoveResponse) || !array_key_exists('warnings', $cscRemoveResponse) || !array_key_exists('errors', $cscRemoveResponse)) {
            throw new ProviderException($this->addLogError('log.opnSenseVpnProvider.deleteCsc.invalidResponse', ['cscCommonName' => $cscCommonName]));
        }

        $warnings = Arr::get($cscRemoveResponse, 'warnings');

        if (null !== $warnings && count($warnings) > 0) {
            $warningsAsString = implode(', ', $warnings);

            $this->addLogWarning('log.opnSenseVpnProvider.deleteCsc.warnings', ['cscCommonName' => $cscCommonName, 'warnings' => $warningsAsString]);
        }

        if ('OK' !== $cscRemoveResponse['status']) {
            // checking structure integrity
            $errors = Arr::get($cscRemoveResponse, 'errors');

            $errorsAsString = (null !== $errors) ? implode(', ', $errors) : 'N/A';

            throw new ProviderException($this->addLogError('log.opnSenseVpnProvider.deleteCsc.errors', ['cscCommonName' => $cscCommonName, 'errors' => $errorsAsString]));
        }
    }

    public function addCscInVpnServer(VpnCscConfiguration $vpnCscConfiguration): void
    {
        $data = $this->getVpnCscConfigurationArray($vpnCscConfiguration);

        $cscAddResponse = $this->httpClient->post(self::ENDPOINT_CSC_ADD, $data);

        $cscAddStatus = Arr::get($cscAddResponse, 'result.status_config_override');

        if (null === $cscAddStatus) {
            throw new ProviderException($this->addLogError('log.opnSenseVpnProvider.addCsc.invalidResponse', ['cscCommonName' => $vpnCscConfiguration->getCscCommonName()]));
        }

        if ('OK' !== $cscAddStatus) {
            // checking structure integrity
            $errors = Arr::get($cscAddResponse, 'result.info_config_override_input_errors');

            $errorsAsString = (null !== $errors) ? implode(', ', $errors) : 'N/A';

            throw new ProviderException($this->addLogError('log.opnSenseVpnProvider.addCsc.errors', ['cscCommonName' => $vpnCscConfiguration->getCscCommonName(), 'errors' => $errorsAsString]));
        }
    }

    // Method allows each provider to calculate CSC configuration hash (using only used values) (Hash is saved in database for future comparisons)
    public function getVpnCscConfigurationHash(VpnCscConfiguration $vpnCscConfiguration): string
    {
        return md5(json_encode($this->getVpnCscConfigurationArray($vpnCscConfiguration)));
    }

    /**
     * Method returns list of firewall rules.
     */
    public function getFirewallRules(): FirewallRuleConfigurationCollection
    {
        $getFirewallRulesResponse = $this->httpClient->post(self::ENDPOINT_FIREWALL_RULES);

        $firewallRulesArray = Arr::get($getFirewallRulesResponse, 'rules');

        if (null === $firewallRulesArray) {
            throw new ProviderException($this->addLogError('log.opnSenseVpnProvider.getFirewallRules.invalidResponse'));
        }

        $firewallRulesCollection = new FirewallRuleConfigurationCollection();

        foreach ($firewallRulesArray as $firewallRuleIndex => $firewallRule) {
            if (!Arr::has($firewallRule, ['md5', 'source.address', 'destination.address'])) {
                continue;
            }

            $sourceIp = Arr::get($firewallRule, 'source.address');
            $destinationIp = Arr::get($firewallRule, 'destination.address');
            $firewallRuleConfiguration = new FirewallRuleConfiguration($sourceIp, $destinationIp);
            $firewallRuleConfiguration->setRuleIdentifier(Arr::get($firewallRule, 'md5'));
            $firewallRuleConfiguration->setRuleIndex((string) $firewallRuleIndex);

            $firewallRulesCollection->add($firewallRuleConfiguration);
        }

        return $firewallRulesCollection;
    }

    /**
     * Method returns rule identifier (md5 hash in case of OpnSense) or throws exception.
     */
    public function addFirewallRule(FirewallRuleConfiguration $firewallRuleConfiguration): string
    {
        $data = [
            'src' => $firewallRuleConfiguration->getSourceIp(),
            'dst' => $firewallRuleConfiguration->getDestinationIp(),
            'srcmask' => $firewallRuleConfiguration->getSourceNetmask(),
            'dstmask' => $firewallRuleConfiguration->getDestinationNetmask(),
            'protocol' => $firewallRuleConfiguration->getProtocol()->value,
            'interface' => 'openvpn',
            'ipprotocol' => 'inet',
            'type' => 'pass',
            'statetype' => 'keep state',
            'direction' => 'any',
        ];

        // If protocol is ANY parameters below should be omited
        if (Protocol::ANY !== $firewallRuleConfiguration->getProtocol()) {
            // '' means 'other' in OPNSense -> OPNSense validation will now require dstbeginport to be set
            $data['srcbeginport'] = $firewallRuleConfiguration->getSourceBeginPort() ? $firewallRuleConfiguration->getSourceBeginPort() : '';
            $data['srcendport'] = $firewallRuleConfiguration->getSourceEndPort() ? $firewallRuleConfiguration->getSourceEndPort() : '';
            $data['dstbeginport'] = $firewallRuleConfiguration->getDestinatioBeginPort() ? $firewallRuleConfiguration->getDestinatioBeginPort() : '';
            $data['dstendport'] = $firewallRuleConfiguration->getDestinatioEndPort() ? $firewallRuleConfiguration->getDestinatioEndPort() : '';
        }

        $addFirewallRuleResponse = $this->httpClient->post(self::ENDPOINT_FIREWALL_ADD_RULE, $data);

        $addFirewallRuleStatus = Arr::get($addFirewallRuleResponse, 'result.status');

        if (null === $addFirewallRuleStatus) {
            throw new ProviderException($this->addLogError('log.opnSenseVpnProvider.addFirewallRule.invalidResponse'));
        }

        if ('OK' !== $addFirewallRuleStatus) {
            // checking structure integrity
            $errors = Arr::get($addFirewallRuleResponse, 'result.input_errors');

            $errorsAsString = (null !== $errors) ? implode(', ', $errors) : 'N/A';

            $this->addLogError('log.opnSenseVpnProvider.addFirewallRule.inputErrors', ['errors' => $errorsAsString]);

            throw new ProviderException($this->addLogError('log.opnSenseVpnProvider.addFirewallRule.statusNotOk', ['status' => $addFirewallRuleStatus]));
        }

        $firewallRuleIdentifier = Arr::get($addFirewallRuleResponse, 'result.md5');

        if (null === $firewallRuleIdentifier) {
            throw new ProviderException($this->addLogError('log.opnSenseVpnProvider.addFirewallRule.missingMd5'));
        }

        return $firewallRuleIdentifier;
    }

    /**
     * Method remove rule or throws exception.
     */
    public function deleteFirewallRule(FirewallRuleConfiguration $firewallRuleConfiguration): void
    {
        if (!$firewallRuleConfiguration->getRuleIndex()) {
            throw new ProviderException($this->addLogError('log.opnSenseVpnProvider.deleteFirewallRule.invalidParameter'));
        }

        $data = [
            'act' => 'del',
            'id' => $firewallRuleConfiguration->getRuleIndex(),
            'sourceIp' => $firewallRuleConfiguration->getSourceIp(),
        ];

        $deleteFirewallRuleResponse = $this->httpClient->post(self::ENDPOINT_FIREWALL_DELETE_RULE, $data);

        $deleteFirewallRuleStatus = Arr::get($deleteFirewallRuleResponse, 'result.status');

        if (null === $deleteFirewallRuleStatus) {
            throw new ProviderException($this->addLogError('log.opnSenseVpnProvider.deleteFirewallRule.invalidResponse'));
        }

        if ('OK' !== $deleteFirewallRuleStatus) {
            throw new ProviderException($this->addLogError('log.opnSenseVpnProvider.deleteFirewallRule.statusNotOk', ['status' => $deleteFirewallRuleStatus]));
        }
    }

    protected function getVpnCscConfigurationArray(VpnCscConfiguration $vpnCscConfiguration): array
    {
        $data = [
            'common_name' => $vpnCscConfiguration->getCscCommonName(),
            'ovpn_servers' => $vpnCscConfiguration->getCscServerName(),
            'tunnel_network' => $vpnCscConfiguration->getTunnelNetwork(),
        ];

        if (strlen($vpnCscConfiguration->getCommaDelimitedRemoteNetworks()) > 0) {
            $data['remote_network'] = $vpnCscConfiguration->getCommaDelimitedRemoteNetworks();
        }

        return $data;
    }
}
