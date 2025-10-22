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
import { Checkbox, FieldsInterface, OptionInterface, Text } from "@arteneo/forge";
import { FormikValues } from "formik";
import { useUser } from "~app/contexts/User";
import CertificateTypeCollection from "~app/components/Form/fields/CertificateTypeCollection";
import { CertificateTypeCollectionProps } from "~app/components/Form/fields/CertificateTypeCollection";
import {
    CertificateAutomaticBehavior,
    CertificateTypeInterface,
    UseableCertificateInterface,
} from "~app/entities/Common/definitions";
import _ from "lodash";

/*
CertificateAutomaticBehaviorStateType - describes currect state of behavior - based on certificate type settings and object state (if in form then current object values)
Certificate type specific behavior will be reduced to one of options below
None - option not available
onDemandEnabled - option is on demand and its available - show field
onDemandDisabled - option is on demand and its NOT available - e.g. revoke if certificate is not generated - hide field or disable field or/and show helper
autoEnabled - option is automatic and should work e.g. automatic genearation and certificate is not generated - hide field or disable field or/and show helper
autoDisabled - option is automatic and will not work e.g. automatic genearation and certificate is already generated - hide field or disable field or/and show helper
*/

/*
On this field component BatchDevicesCertificateAutomaticBehaviorCollection, DeviceCertificateAutomaticBehaviorCollection and UserCertificateAutomaticBehaviorCollection are based
*/

interface CertificateAutomaticBehaviorCollectionProps
    extends Omit<CertificateTypeCollectionProps, "fields" | "certificateTypes"> {
    certificateAutomaticBehaviors: CertificateAutomaticBehavior[];
}

// Component requires correct changeSubmitValues to send correct data to endpoint
const changeSubmitValuesBatchCertificateAutomaticBehaviorCollection = (
    values: FormikValues,
    useableCertificates: UseableCertificateInterface[]
) => {
    const _values = _.cloneDeep(values);

    if (!_values.certificateBehaviours) {
        return _values;
    }

    _values.certificateBehaviours = _values.certificateBehaviours
        .map((certificateBehaviour: FormikValues) => {
            if (!certificateBehaviour?.certificateType?.id) {
                return null;
            }

            const certificateTypeExists = useableCertificates.find(
                (useableCertificate) =>
                    useableCertificate?.certificateType?.id == certificateBehaviour?.certificateType?.id
            );

            if (undefined === certificateTypeExists) {
                return null;
            }

            let revokeCertificate = certificateBehaviour?.revokeCertificate;
            if (certificateBehaviour?.revokeCertificate) {
                if (
                    !certificateTypeExists.certificate ||
                    !certificateTypeExists.certificate?.hasCertificate ||
                    !certificateTypeExists.certificate?.certificateGenerated ||
                    certificateTypeExists?.deny?.revokeCertificate
                ) {
                    revokeCertificate = false;
                }
            }

            let generateCertificate = certificateBehaviour?.generateCertificate;
            if (certificateBehaviour?.generateCertificate) {
                if (
                    certificateTypeExists.certificate?.hasCertificate ||
                    certificateTypeExists.certificate?.certificateGenerated ||
                    certificateTypeExists?.deny?.generateCertificate
                ) {
                    generateCertificate = false;
                }
            }

            return {
                certificateType: certificateBehaviour?.certificateType?.id,
                generateCertificate: generateCertificate ?? false,
                revokeCertificate: revokeCertificate ?? false,
            };
        })
        .filter((certificateBehaviour: FormikValues) => certificateBehaviour !== null);

    return _values;
};

// Component requires correct changeSubmitValues to send correct data to endpoint
const changeSubmitValuesCertificateAutomaticBehaviorCollection = (values: FormikValues) => {
    const _values = _.cloneDeep(values);

    if (!values.certificateBehaviours) {
        return _values;
    }

    _values.certificateBehaviours = values.certificateBehaviours.map((certificateBehaviour: FormikValues) => {
        return {
            certificateType: certificateBehaviour?.certificateType?.id,
            generateCertificate: certificateBehaviour?.generateCertificate ?? false,
            revokeCertificate: certificateBehaviour?.revokeCertificate ?? false,
        };
    });

    return _values;
};

const CertificateAutomaticBehaviorCollection = ({
    certificateAutomaticBehaviors,
    ...certificateTypeCollectionProps
}: CertificateAutomaticBehaviorCollectionProps) => {
    const { isAccessGranted } = useUser();

    const isScepSecuritySuite = isAccessGranted({ adminScep: true });

    if (!isScepSecuritySuite) {
        return null;
    }

    const certificateTypes = certificateAutomaticBehaviors?.map(
        (certificateAutomaticBehavior: CertificateAutomaticBehavior) => {
            return certificateAutomaticBehavior?.certificateType;
        }
    );

    const getFieldProps = (fieldName: string, certificateType: CertificateTypeInterface) => {
        const certificateAutomaticBehavior = certificateAutomaticBehaviors?.find(
            (certificateAutomaticBehavior: CertificateAutomaticBehavior) => {
                return certificateAutomaticBehavior?.certificateType?.id == certificateType?.id;
            }
        );

        if (fieldName == "generateCertificate") {
            if (certificateAutomaticBehavior?.generateCertificate === "onDemandEnabled") {
                return {};
            }
            if (certificateAutomaticBehavior?.generateCertificate === "autoEnabled") {
                return {
                    disabled: true,
                    //faking checked checkbox - not to mess with form values
                    formControlLabelProps: {
                        checked: true,
                    },
                    help: "help.generateCertificateAuto",
                };
            }

            return {
                hidden: true,
            };
        }

        if (fieldName == "revokeCertificate") {
            if (certificateAutomaticBehavior?.revokeCertificate === "onDemandEnabled") {
                return {};
            }
            if (certificateAutomaticBehavior?.revokeCertificate === "autoEnabled") {
                return {
                    disabled: true,
                    //faking checked checkbox - not to mess with form values
                    formControlLabelProps: {
                        checked: true,
                    },
                    help: "help.revokeCertificateAuto",
                };
            }

            return {
                hidden: true,
            };
        }

        return {};
    };

    const isRowHidden = (certificateType: OptionInterface) => {
        const certificateAutomaticBehavior = certificateAutomaticBehaviors?.find(
            (certificateAutomaticBehavior: CertificateAutomaticBehavior) => {
                return certificateAutomaticBehavior?.certificateType?.id == certificateType?.id;
            }
        );

        if (
            certificateAutomaticBehavior?.generateCertificate == "onDemandEnabled" ||
            certificateAutomaticBehavior?.generateCertificate == "autoEnabled" ||
            certificateAutomaticBehavior?.revokeCertificate == "onDemandEnabled" ||
            certificateAutomaticBehavior?.revokeCertificate == "autoEnabled"
        ) {
            return false;
        }
        return true;
    };

    const fields: FieldsInterface = {
        certificateType: (
            <Text
                {...{
                    hidden: true,
                    label: "certificateType",
                }}
            />
        ),
        generateCertificate: (
            <Checkbox
                {...{
                    label: "onDemandGenerateCertificate",
                }}
            />
        ),
        revokeCertificate: (
            <Checkbox
                {...{
                    label: "onDemandRevokeCertificate",
                }}
            />
        ),
    };

    return (
        <CertificateTypeCollection
            {...{
                disableAutoLabel: true,
                indent: true,
                certificateTypes: certificateTypes,
                fields: fields,
                isRowHidden: isRowHidden,
                getFieldProps: getFieldProps,
                ...certificateTypeCollectionProps,
            }}
        />
    );
};

export default CertificateAutomaticBehaviorCollection;
export {
    CertificateAutomaticBehaviorCollectionProps,
    changeSubmitValuesCertificateAutomaticBehaviorCollection,
    changeSubmitValuesBatchCertificateAutomaticBehaviorCollection,
    CertificateAutomaticBehavior,
};
