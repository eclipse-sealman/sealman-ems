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
import { useUser } from "~app/contexts/User";
import { CertificateTypeCollectionProps } from "~app/components/Form/fields/CertificateTypeCollection";
import {
    CertificateAutomaticBehaviorExtended,
    CertificateAutomaticBehaviorStateType,
    CertificateTypeInterface,
} from "~app/entities/Common/definitions";
import CertificateAutomaticBehaviorCollection from "~app/components/Form/fields/CertificateAutomaticBehaviorCollection";
import { useForm } from "@arteneo/forge";
import { FormikProps, FormikValues, useFormikContext } from "formik";

interface BatchDevicesCertificateAutomaticBehaviorCollectionProps
    extends Omit<CertificateTypeCollectionProps, "fields" | "certificateTypes"> {
    enable: boolean;
}

const BatchDevicesCertificateAutomaticBehaviorCollection = ({
    enable,
    ...certificateTypeCollectionProps
}: BatchDevicesCertificateAutomaticBehaviorCollectionProps) => {
    const { setFieldValue }: FormikProps<FormikValues> = useFormikContext();
    const [certificateAutomaticBehaviors, setCertificateAutomaticBehaviors] = React.useState<
        null | CertificateAutomaticBehaviorExtended[]
    >(null);
    const { isAccessGranted } = useUser();
    const { initialValues } = useForm();

    const isScepSecuritySuite = isAccessGranted({ adminScep: true });
    const isVpnSecuritySuite = isAccessGranted({ adminVpn: true });

    if (!initialValues?.useableCertificates) {
        return null;
    }

    if (!isScepSecuritySuite) {
        return null;
    }

    React.useEffect(() => calculateCertificateAutomaticBehaviors(), []);

    const calculateCertificateAutomaticBehaviors = () => {
        if (!isScepSecuritySuite) {
            return;
        }

        const _certificateAutomaticBehaviors = initialValues?.useableCertificates.map(
            (certificateType: CertificateTypeInterface, index: number | string) => {
                setFieldValue("certificateBehaviours." + index + ".certificateType", certificateType);

                let canGenerateCertificate = enable;

                let canRevokeCertificate = !enable;

                if (certificateType?.pkiEnabled !== true) {
                    canGenerateCertificate = false;
                    canRevokeCertificate = false;
                }

                let generateCertificate: CertificateAutomaticBehaviorStateType = "none";
                let revokeCertificate: CertificateAutomaticBehaviorStateType = "none";

                if (enable) {
                    if (certificateType.enabledBehaviour == "auto") {
                        if (canGenerateCertificate) {
                            generateCertificate = "autoEnabled";
                        } else {
                            generateCertificate = "autoDisabled";
                        }
                    }
                    if (certificateType.enabledBehaviour == "onDemand") {
                        if (canGenerateCertificate) {
                            generateCertificate = "onDemandEnabled";
                        } else {
                            generateCertificate = "onDemandDisabled";
                        }
                    }
                    if (certificateType.enabledBehaviour == "specific") {
                        //For now only deviceVpn has specific behavior (for devices)
                        if (certificateType.certificateCategory == "deviceVpn") {
                            if (isVpnSecuritySuite) {
                                if (canGenerateCertificate) {
                                    generateCertificate = "autoEnabled";
                                } else {
                                    generateCertificate = "autoDisabled";
                                }
                            } //else none
                        }
                    }
                } else {
                    if (certificateType.disabledBehaviour == "auto") {
                        if (canRevokeCertificate) {
                            revokeCertificate = "autoEnabled";
                        } else {
                            revokeCertificate = "autoDisabled";
                        }
                    }
                    if (certificateType.disabledBehaviour == "onDemand") {
                        if (canRevokeCertificate) {
                            revokeCertificate = "onDemandEnabled";
                        } else {
                            revokeCertificate = "onDemandDisabled";
                        }
                    }
                    if (certificateType.disabledBehaviour == "specific") {
                        //For now only deviceVpn has specific behavior (for devices)
                        if (certificateType.certificateCategory == "deviceVpn") {
                            if (isVpnSecuritySuite) {
                                if (canRevokeCertificate) {
                                    revokeCertificate = "onDemandEnabled";
                                } else {
                                    revokeCertificate = "onDemandDisabled";
                                }
                            } //else none
                        }
                    }
                }

                const certificateAutomaticBehaviorExtended: CertificateAutomaticBehaviorExtended = {
                    certificateType: certificateType,
                    generateCertificate: generateCertificate,
                    revokeCertificate: revokeCertificate,
                    canGenerateCertificate: canGenerateCertificate,
                    canRevokeCertificate: canRevokeCertificate,
                };

                return certificateAutomaticBehaviorExtended;
            }
        );

        setCertificateAutomaticBehaviors(_certificateAutomaticBehaviors);
    };

    if (!certificateAutomaticBehaviors) {
        return null;
    }

    return (
        <CertificateAutomaticBehaviorCollection
            {...{
                certificateAutomaticBehaviors: certificateAutomaticBehaviors,
                ...certificateTypeCollectionProps,
            }}
        />
    );
};

export default BatchDevicesCertificateAutomaticBehaviorCollection;
export { BatchDevicesCertificateAutomaticBehaviorCollectionProps };
