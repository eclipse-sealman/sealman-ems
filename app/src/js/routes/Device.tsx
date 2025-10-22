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

import React from "react";
import { RouterOutlined } from "@mui/icons-material";
import getColumns from "~app/entities/Device/columns";
import getFilters from "~app/entities/Device/filters";
import Builder from "~app/components/Crud/Builder";
import DeviceDetails from "~app/components/Details/Device/DeviceDetails";
import BuilderToolbar from "~app/components/Table/toolbar/BuilderToolbar";
import BatchButtonExpand from "~app/components/Table/toolbar/BatchButtonExpand";
import BatchDisable from "~app/entities/Device/toolbar/BatchDisable";
import BatchEnable from "~app/entities/Device/toolbar/BatchEnable";
import BatchDelete from "~app/entities/Device/toolbar/BatchDelete";
import BatchVariableAdd from "~app/entities/Device/toolbar/BatchVariableAdd";
import BatchVariableDelete from "~app/entities/Device/toolbar/BatchVariableDelete";
import BatchTemplateApply from "~app/entities/Device/toolbar/BatchTemplateApply";
import BatchAccessTagsAdd from "~app/entities/Device/toolbar/BatchAccessTagsAdd";
import BatchAccessTagsDelete from "~app/entities/Device/toolbar/BatchAccessTagsDelete";
import BatchReinstallConfig1 from "~app/entities/Device/toolbar/BatchReinstallConfig1";
import BatchReinstallConfig2 from "~app/entities/Device/toolbar/BatchReinstallConfig2";
import BatchReinstallConfig3 from "~app/entities/Device/toolbar/BatchReinstallConfig3";
import BatchReinstallFirmware1 from "~app/entities/Device/toolbar/BatchReinstallFirmware1";
import BatchReinstallFirmware2 from "~app/entities/Device/toolbar/BatchReinstallFirmware2";
import BatchReinstallFirmware3 from "~app/entities/Device/toolbar/BatchReinstallFirmware3";
import BatchRequestConfigData from "~app/entities/Device/toolbar/BatchRequestConfigData";
import BatchRequestDiagnoseData from "~app/entities/Device/toolbar/BatchRequestDiagnoseData";
import BatchLabelsAdd from "~app/entities/Device/toolbar/BatchLabelsAdd";
import BatchLabelsDelete from "~app/entities/Device/toolbar/BatchLabelsDelete";
import { useUser } from "~app/contexts/User";

const Device = () => {
    const { isAccessGranted } = useUser();

    const adminColumnNames = ["uuid"];
    const adminVpnColumnNames = ["masqueradeType", "virtualSubnetCidr"];

    const vpnColumnNames = [
        "description",
        "vpnConnected",
        "vpnIp",
        "virtualSubnet",
        "virtualIp",
        "vpnTrafficIn",
        "vpnTrafficOut",
        "vpnLastConnectionAt",
    ];

    const smartemsColumnNames = [
        "accessTags",
        "staging",
        "connectionAmount",
        "reinstallFirmware1",
        "reinstallFirmware2",
        "reinstallFirmware3",
        "reinstallConfig1",
        "reinstallConfig2",
        "reinstallConfig3",
        "requestDiagnoseData",
        "requestConfigData",
        "commandRetryCount",
        "xForwardedFor",
        "host",
        "ipv6Prefix",
        "uptime",
        "uptimeSeconds",
        "cellId",
        "cellularIp1",
        "cellularUptime1",
        "cellularUptimeSeconds1",
        "cellularIp2",
        "cellularUptime2",
        "cellularUptimeSeconds2",
    ];

    const columns = getColumns(undefined, [
        ...(isAccessGranted({ admin: true }) ? [] : adminColumnNames),
        ...(isAccessGranted({ adminVpn: true }) ? [] : adminVpnColumnNames),
        ...(isAccessGranted({ adminVpn: true, vpn: true }) ? [] : vpnColumnNames),
        ...(isAccessGranted({ admin: true, smartems: true }) ? [] : smartemsColumnNames),
    ]);

    const adminFiltersNames = ["updatedBy", "createdBy"];
    const vpnFiltersNames = ["vpnConnected"];

    const smartemsFiltersNames = ["accessTags", "staging"];

    const filters = getFilters(undefined, [
        ...(isAccessGranted({ admin: true }) ? [] : adminFiltersNames),
        ...(isAccessGranted({ adminVpn: true, vpn: true }) ? [] : vpnFiltersNames),
        ...(isAccessGranted({ admin: true, smartems: true }) ? [] : smartemsFiltersNames),
    ]);

    return (
        <Builder
            {...{
                endpointPrefix: "/device",
                title: "route.title.device",
                icon: <RouterOutlined />,
                listProps: {
                    enableBatchSelect: isAccessGranted({ admin: true, smartems: true }),
                    toolbar: (
                        <BuilderToolbar
                            {...{
                                render: ({ createAction, exportCsvAction, exportExcelAction }) => (
                                    <>
                                        {isAccessGranted({ admin: true, smartems: true }) && (
                                            <BatchButtonExpand>
                                                <BatchDisable />
                                                <BatchEnable />
                                                <BatchReinstallConfig1 />
                                                <BatchReinstallConfig2 />
                                                <BatchReinstallConfig3 />
                                                <BatchReinstallFirmware1 />
                                                <BatchReinstallFirmware2 />
                                                <BatchReinstallFirmware3 />
                                                <BatchRequestConfigData />
                                                <BatchRequestDiagnoseData />
                                                <BatchVariableAdd />
                                                <BatchVariableDelete />
                                                <BatchTemplateApply />
                                                <BatchAccessTagsAdd />
                                                <BatchAccessTagsDelete />
                                                <BatchLabelsAdd />
                                                <BatchLabelsDelete />
                                                {isAccessGranted({ admin: true }) && <BatchDelete />}
                                            </BatchButtonExpand>
                                        )}
                                        {isAccessGranted({ admin: true, smartems: true }) && <>{createAction}</>}
                                        {exportCsvAction}
                                        {exportExcelAction}
                                    </>
                                ),
                            }}
                        />
                    ),
                    columns,
                    filters,
                    hasCreate: true,
                    hasEdit: true,
                    defaultSorting: {
                        createdAt: "desc",
                    },
                    visibleColumnsKey: "device",
                    defaultColumns: [
                        "deviceType",
                        "identifier",
                        "enabled",
                        ...(isAccessGranted({ adminScep: true }) && !isAccessGranted({ adminVpn: true })
                            ? ["hasCertificate"]
                            : []),
                        ...(isAccessGranted({ adminVpn: true, vpn: true }) ? ["vpnConnected", "hasCertificate"] : []),
                        "actions",
                    ],
                    deleteProps: {
                        denyBehavior: "hide",
                    },
                },
                detailsProps: {
                    objectTitleProps: (object) => ({
                        subtitle: "route.subtitle.detailsRepresentationDeviceType",
                        subtitleVariables: {
                            representation: object.representation,
                            deviceType: object.deviceType?.name,
                        },
                        disableSubtitleTranslate: false,
                    }),
                    render: (object) => <DeviceDetails {...{ device: object }} />,
                },
            }}
        />
    );
};

export default Device;
