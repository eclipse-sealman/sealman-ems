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
import { ButtonDownload, FieldsInterface, renderField } from "@arteneo/forge";
import { Alert, Box } from "@mui/material";
import { DownloadOutlined } from "@mui/icons-material";
import { useUtils } from "@mui/x-date-pickers/internals";
import { useTranslation } from "react-i18next";
import { FormikValues, useFormikContext } from "formik";
import { format } from "date-fns";
import { useConfiguration } from "~app/contexts/Configuration";
import CrudFormView, { CrudFormViewProps } from "~app/views/CrudFormView";
import ButtonDialogMonaco from "~app/components/Common/ButtonDialogMonaco";

interface ConfigurationSsoFieldsetProps extends Omit<CrudFormViewProps, "children"> {
    fields: FieldsInterface;
}

const ConfigurationSsoFieldset = ({ fields, ...formViewProps }: ConfigurationSsoFieldsetProps) => {
    const { t } = useTranslation();
    const { microsoftOidcUploadedCertificatePublicValidTo, microsoftOidcGeneratedCertificatePublicValidTo } =
        useConfiguration();
    const { values } = useFormikContext<FormikValues>();
    const utils = useUtils();

    const render = renderField(fields);

    const isMicrosoftOidc = values?.["singleSignOn"] === "microsoftOidc" ? true : false;
    const isCertificateUpload =
        isMicrosoftOidc && values?.["microsoftOidcCredential"] === "certificateUpload" ? true : false;
    const isCertificateGenerate =
        isMicrosoftOidc && values?.["microsoftOidcCredential"] === "certificateGenerate" ? true : false;
    const isCertificateUploaded = microsoftOidcUploadedCertificatePublicValidTo ? true : false;
    const isCertificateGenerated = microsoftOidcGeneratedCertificatePublicValidTo ? true : false;

    const showCertificateUploaded = isCertificateUpload && isCertificateUploaded ? true : false;
    // Upload input is problematic and Chrome does not properly show that it is required (browser hint that field is required). Let backend handle it.
    const certificateUploadRequired = false;

    const showCertificateGenerated = isCertificateGenerate && isCertificateGenerated ? true : false;
    const certificateGenerateRequired = isCertificateGenerate && !isCertificateGenerated ? true : false;

    let uploadedValidTo = utils.date(microsoftOidcUploadedCertificatePublicValidTo);
    if (uploadedValidTo != "Invalid Date") {
        uploadedValidTo = format(uploadedValidTo as Date, "dd-MM-yyyy HH:mm:ss");
    } else {
        uploadedValidTo = t("label.unknown");
    }

    let generatedValidTo = utils.date(microsoftOidcGeneratedCertificatePublicValidTo);
    if (generatedValidTo != "Invalid Date") {
        generatedValidTo = format(generatedValidTo as Date, "dd-MM-yyyy HH:mm:ss");
    } else {
        generatedValidTo = t("label.unknown");
    }

    return (
        <CrudFormView {...formViewProps}>
            <Box {...{ sx: { display: "flex", flexDirection: "column", gap: 3 } }}>
                {render("singleSignOn")}
                {render("ssoAllowCustomRedirectUrl")}
                {fields.ssoRoleVpnCertificateAutoGenerate && render("ssoRoleVpnCertificateAutoGenerate")}
                {render("microsoftOidcAppId")}
                {render("microsoftOidcDirectoryId")}
                {render("microsoftOidcTimeout")}
                {render("microsoftOidcCredential")}
                {render("microsoftOidcClientSecret")}
                {showCertificateUploaded && (
                    <Box {...{ sx: { display: "flex" } }}>
                        <Alert {...{ severity: "warning", sx: { flexGrow: 1 } }}>
                            {t("configuration.sso.microsoftOidcCredential.certificateUploaded", {
                                validTo: uploadedValidTo,
                            })}
                            <Box {...{ sx: { display: "flex", alignItems: "center", gap: 2, mt: 2 } }}>
                                <ButtonDialogMonaco
                                    {...{
                                        label: "configuration.sso.microsoftOidcCredential.show",
                                        size: "medium",
                                        dialogProps: {
                                            initializeEndpoint:
                                                "/configuration/sso/microsoftoidc/credential/uploadedcertificate/public",
                                            content: (result) => result,
                                            title: "configuration.sso.microsoftOidcCredential.show",
                                            language: "plain",
                                        },
                                    }}
                                />
                                <ButtonDownload
                                    {...{
                                        label: "configuration.sso.microsoftOidcCredential.download",
                                        variant: "contained",
                                        color: "success",
                                        startIcon: <DownloadOutlined />,
                                        endpoint:
                                            "/configuration/sso/microsoftoidc/credential/uploadedcertificate/public/download",
                                    }}
                                />
                            </Box>
                        </Alert>
                    </Box>
                )}
                {render("microsoftOidcUploadedCertificatePublic", { required: certificateUploadRequired })}
                {render("microsoftOidcUploadedCertificatePrivate", { required: certificateUploadRequired })}
                {showCertificateGenerated && (
                    <Box {...{ sx: { display: "flex" } }}>
                        <Alert {...{ severity: "warning", sx: { flexGrow: 1 } }}>
                            {t("configuration.sso.microsoftOidcCredential.certificateGenerated", {
                                validTo: generatedValidTo,
                            })}
                            <Box {...{ sx: { display: "flex", alignItems: "center", gap: 2, mt: 2 } }}>
                                <ButtonDialogMonaco
                                    {...{
                                        label: "configuration.sso.microsoftOidcCredential.show",
                                        size: "medium",
                                        dialogProps: {
                                            initializeEndpoint:
                                                "/configuration/sso/microsoftoidc/credential/generatedcertificate/public",
                                            content: (result) => result,
                                            title: "configuration.sso.microsoftOidcCredential.show",
                                            language: "plain",
                                        },
                                    }}
                                />
                                <ButtonDownload
                                    {...{
                                        label: "configuration.sso.microsoftOidcCredential.download",
                                        variant: "contained",
                                        color: "success",
                                        startIcon: <DownloadOutlined />,
                                        endpoint:
                                            "/configuration/sso/microsoftoidc/credential/generatedcertificate/public/download",
                                    }}
                                />
                            </Box>
                        </Alert>
                    </Box>
                )}
                {render("microsoftOidcGenerateCertificate", { required: certificateGenerateRequired })}
                {render("microsoftOidcGenerateCertificateExpiryDays")}
                <Box
                    {...{
                        sx: {
                            // Selector for microsoftOidcRole field
                            "& .ForgeCollectionTable-root th:nth-of-type(2)": {
                                width: "18rem",
                            },
                            // Selector for roleVpnEndpointDevices field
                            "& .ForgeCollectionTable-root th:nth-of-type(4)": {
                                width: "14rem",
                            },
                        },
                    }}
                >
                    {render("microsoftOidcRoleMappings")}
                </Box>
            </Box>
        </CrudFormView>
    );
};

export default ConfigurationSsoFieldset;
export { ConfigurationSsoFieldsetProps };
