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
import { Checkbox, CheckboxProps, useForm } from "@arteneo/forge";
import { FormikValues, FormikProps, useFormikContext, getIn } from "formik";
import { useUser } from "~app/contexts/User";

interface UserGenerateCertificateCheckboxProps extends CheckboxProps {
    enabledPath?: string;
    roleAdminPath?: string;
    roleSmartemsPath?: string;
    roleVpnPath?: string;
}

const UserGenerateCertificateCheckbox = ({
    enabledPath = "enabled",
    roleAdminPath = "roleAdmin",
    roleSmartemsPath = "roleSmartems",
    roleVpnPath = "roleVpn",
    ...checkboxProps
}: UserGenerateCertificateCheckboxProps) => {
    const [initialEnabled, setInitialEnabled] = React.useState<null | boolean>(null);
    const { values, setFieldValue }: FormikProps<FormikValues> = useFormikContext();
    const { initialValues } = useForm();
    const { isAccessGranted } = useUser();

    //If only SCEP license enabled, certificate generation is manual (doesn't make sense to do it automatically without VPN)
    const isVpnSecuritySuite = isAccessGranted({ adminVpn: true });
    const certificateGenerated = initialValues?.hasCertificate;
    const roleAdmin = getIn(values, roleAdminPath, false);
    const roleSmartems = getIn(values, roleSmartemsPath, false);
    const roleVpn = getIn(values, roleVpnPath, false);
    const enabled = getIn(values, enabledPath, false);

    React.useEffect(() => load(), []);
    React.useEffect(() => update(), [roleAdmin, roleVpn, roleSmartems, enabled]);

    const load = () => {
        setInitialEnabled(getIn(values, enabledPath, null));
    };

    const update = () => {
        if (!isVpnSecuritySuite) {
            return;
        }

        if (certificateGenerated) {
            return;
        }

        if (roleAdmin) {
            return;
        }

        if (roleVpn) {
            setFieldValue("generateCertificate", enabled);
        } else if (roleSmartems) {
            setFieldValue("generateCertificate", false);
        }
    };

    if (!isVpnSecuritySuite) {
        return null;
    }

    if (certificateGenerated) {
        return null;
    }

    if (initialEnabled === true) {
        return null;
    }

    if (!roleAdmin || !enabled) {
        return null;
    }

    return <Checkbox {...checkboxProps} />;
};

export default UserGenerateCertificateCheckbox;
export { UserGenerateCertificateCheckboxProps };
