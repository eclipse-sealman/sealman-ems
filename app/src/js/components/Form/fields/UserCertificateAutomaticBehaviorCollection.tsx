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
import { useForm } from "@arteneo/forge";
import { FormikValues, FormikProps, useFormikContext, getIn } from "formik";
import { useUser } from "~app/contexts/User";
import { CertificateTypeCollectionProps } from "~app/components/Form/fields/CertificateTypeCollection";
import {
    CertificateAutomaticBehaviorExtended,
    CertificateAutomaticBehaviorStateType,
    UseableCertificateInterface,
} from "~app/entities/Common/definitions";
import CertificateAutomaticBehaviorCollection from "~app/components/Form/fields/CertificateAutomaticBehaviorCollection";

type UserCertificateAutomaticBehaviorCollectionProps = Omit<
    CertificateTypeCollectionProps,
    "fields" | "certificateTypes"
>;

const UserCertificateAutomaticBehaviorCollection = ({
    ...certificateTypeCollectionProps
}: UserCertificateAutomaticBehaviorCollectionProps) => {
    const { values, setFieldValue }: FormikProps<FormikValues> = useFormikContext();
    const [certificateAutomaticBehaviors, setCertificateAutomaticBehaviors] = React.useState<
        null | CertificateAutomaticBehaviorExtended[]
    >(null);
    const { initialValues, initializedValuesResponse } = useForm();
    const { isAccessGranted } = useUser();

    const isScepSecuritySuite = isAccessGranted({ adminScep: true });
    const isVpnSecuritySuite = isAccessGranted({ adminVpn: true });

    const resolvedInitialValues =
        initializedValuesResponse !== undefined ? initializedValuesResponse.data : initialValues;

    const enabled = getIn(values, "enabled", getIn(resolvedInitialValues, "enabled", false));
    const roleAdmin = getIn(values, "roleAdmin", getIn(resolvedInitialValues, "roleAdmin", false));
    const roleVpn = getIn(values, "roleVpn", getIn(resolvedInitialValues, "roleVpn", false));
    const useableCertificates = getIn(
        values,
        "useableCertificates",
        getIn(resolvedInitialValues, "useableCertificates", [])
    );

    if (!isScepSecuritySuite) {
        return null;
    }

    if (!useableCertificates) {
        return null;
    }

    React.useEffect(() => update(), [enabled, roleAdmin, roleVpn]);

    const update = () => {
        if (!isScepSecuritySuite) {
            return;
        }

        let _certificateAutomaticBehaviors = certificateAutomaticBehaviors;

        if (!_certificateAutomaticBehaviors) {
            _certificateAutomaticBehaviors = useableCertificates.map(
                (useableCertificate: UseableCertificateInterface, index: number | string) => {
                    let canGenerateCertificate =
                        useableCertificate?.certificate?.hasCertificate === true ? false : true;

                    let canRevokeCertificate =
                        useableCertificate?.certificate?.hasCertificate === true &&
                        useableCertificate?.certificate?.certificateGenerated === true;

                    if (useableCertificate?.certificateType?.pkiEnabled !== true) {
                        canGenerateCertificate = false;
                        canRevokeCertificate = false;
                    }

                    setFieldValue(
                        "certificateBehaviours." + index + ".certificateType",
                        useableCertificate?.certificateType
                    );

                    return {
                        certificateType: useableCertificate?.certificateType,
                        generateCertificate: "none",
                        revokeCertificate: "none",
                        canGenerateCertificate: canGenerateCertificate,
                        canRevokeCertificate: canRevokeCertificate,
                    };
                }
            );
        }

        if (_certificateAutomaticBehaviors) {
            _certificateAutomaticBehaviors = _certificateAutomaticBehaviors.map(
                (certificateAutomaticBehavior: CertificateAutomaticBehaviorExtended, index: number | string) => {
                    if (enabled) {
                        setFieldValue("certificateBehaviours." + index + ".revokeCertificate", false);

                        let generateCertificate: CertificateAutomaticBehaviorStateType = "none";

                        if (certificateAutomaticBehavior.certificateType.enabledBehaviour == "auto") {
                            if (certificateAutomaticBehavior.canGenerateCertificate) {
                                generateCertificate = "autoEnabled";
                            } else {
                                generateCertificate = "autoDisabled";
                            }
                        }
                        if (certificateAutomaticBehavior.certificateType.enabledBehaviour == "onDemand") {
                            if (certificateAutomaticBehavior.canGenerateCertificate) {
                                generateCertificate = "onDemandEnabled";
                            } else {
                                generateCertificate = "onDemandDisabled";
                            }
                        }
                        if (certificateAutomaticBehavior.certificateType.enabledBehaviour == "specific") {
                            //For now only technicianVpn has specific behavior (for users)
                            if (certificateAutomaticBehavior.certificateType.certificateCategory == "technicianVpn") {
                                if (isVpnSecuritySuite) {
                                    if (roleAdmin) {
                                        if (certificateAutomaticBehavior.canGenerateCertificate) {
                                            generateCertificate = "onDemandEnabled";
                                        } else {
                                            generateCertificate = "onDemandDisabled";
                                        }
                                    }
                                    if (roleVpn) {
                                        if (certificateAutomaticBehavior.canGenerateCertificate) {
                                            generateCertificate = "autoEnabled";
                                        } else {
                                            generateCertificate = "autoDisabled";
                                        }
                                    }
                                } //else none
                            }
                        }

                        return {
                            ...certificateAutomaticBehavior,
                            generateCertificate: generateCertificate,
                            revokeCertificate: "none",
                        };
                    } else {
                        setFieldValue("certificateBehaviours." + index + ".generateCertificate", false);
                        let revokeCertificate: CertificateAutomaticBehaviorStateType = "none";

                        if (certificateAutomaticBehavior.certificateType.disabledBehaviour == "auto") {
                            if (certificateAutomaticBehavior.canRevokeCertificate) {
                                revokeCertificate = "autoEnabled";
                            } else {
                                revokeCertificate = "autoDisabled";
                            }
                        }
                        if (certificateAutomaticBehavior.certificateType.disabledBehaviour == "onDemand") {
                            if (certificateAutomaticBehavior.canRevokeCertificate) {
                                revokeCertificate = "onDemandEnabled";
                            } else {
                                revokeCertificate = "onDemandDisabled";
                            }
                        }
                        if (certificateAutomaticBehavior.certificateType.disabledBehaviour == "specific") {
                            //For now only technicianVpn has specific behavior (for users)
                            if (certificateAutomaticBehavior.certificateType.certificateCategory == "technicianVpn") {
                                if (isVpnSecuritySuite) {
                                    if (roleAdmin) {
                                        if (certificateAutomaticBehavior.canRevokeCertificate) {
                                            revokeCertificate = "onDemandEnabled";
                                        } else {
                                            revokeCertificate = "onDemandDisabled";
                                        }
                                    }
                                    if (roleVpn) {
                                        if (certificateAutomaticBehavior.canRevokeCertificate) {
                                            revokeCertificate = "autoEnabled";
                                        } else {
                                            revokeCertificate = "autoDisabled";
                                        }
                                    }
                                } //else none
                            }
                        }

                        return {
                            ...certificateAutomaticBehavior,
                            generateCertificate: "none",
                            revokeCertificate: revokeCertificate,
                        };
                    }
                }
            );
        }

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

export default UserCertificateAutomaticBehaviorCollection;
export { UserCertificateAutomaticBehaviorCollectionProps };
