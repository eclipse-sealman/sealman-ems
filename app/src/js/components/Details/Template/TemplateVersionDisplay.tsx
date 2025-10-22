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
import axios from "axios";
import { useHandleCatch, useLoader } from "@arteneo/forge";
import { Box } from "@mui/material";
import EntityInterface from "~app/definitions/EntityInterface";
import { TemplateVersionInterface } from "~app/entities/TemplateVersion/definitions";
import Display from "~app/components/Display/Display";
import composeGetRows, { composeGetTitleProps } from "~app/entities/TemplateVersion/rows";
import ResultEdit from "~app/components/Table/actions/ResultEdit";
import DetachStaging from "~app/entities/TemplateVersion/actions/DetachStaging";
import DetachProduction from "~app/entities/TemplateVersion/actions/DetachProduction";
import SelectStaging from "~app/entities/TemplateVersion/actions/SelectStaging";
import SelectProduction from "~app/entities/TemplateVersion/actions/SelectProduction";
import { useUser } from "~app/contexts/User";

interface TemplateVersionDisplayProps {
    templateVersion: EntityInterface;
}

const TemplateVersionDisplay = ({ templateVersion: templateVersionProp }: TemplateVersionDisplayProps) => {
    const { isAccessGranted } = useUser();
    const handleCatch = useHandleCatch();
    const { showLoader, hideLoader } = useLoader();

    const [templateVersion, setTemplateVersion] = React.useState<undefined | TemplateVersionInterface>(undefined);

    const templateVersionId = templateVersionProp.id;
    React.useEffect(() => load(), [templateVersionId]);

    const load = () => {
        showLoader();
        setTemplateVersion(undefined);

        axios
            .get("/templateversion/" + templateVersionId)
            .then((response) => {
                setTemplateVersion(response.data);
                hideLoader();
            })
            .catch((error) => {
                hideLoader();
                handleCatch(error);
            });
    };

    if (typeof templateVersion === "undefined") {
        return null;
    }

    const getRows = composeGetRows(
        templateVersion.deviceType,
        !isAccessGranted({ admin: true }),
        !isAccessGranted({ adminVpn: true })
    );
    const rows = getRows();

    const getTitleProps = composeGetTitleProps(templateVersion);

    return (
        <>
            <Display
                {...{
                    result: templateVersion,
                    rows,
                    getTitleProps,
                }}
            />
            <Box {...{ sx: { display: "flex", justifyContent: "space-between", gap: 2, mt: 2 } }}>
                <Box {...{ sx: { display: "flex", gap: 2 } }}>
                    <DetachStaging {...{ result: templateVersion }} />
                    <DetachProduction {...{ result: templateVersion }} />
                    <SelectStaging {...{ result: templateVersion }} />
                    <SelectProduction {...{ result: templateVersion }} />
                </Box>
                <Box>
                    <ResultEdit
                        {...{
                            result: templateVersion,
                            to: (result) => "/templateversion/edit/" + result.id,
                        }}
                    />
                </Box>
            </Box>
        </>
    );
};

export default TemplateVersionDisplay;
export { TemplateVersionDisplayProps };
