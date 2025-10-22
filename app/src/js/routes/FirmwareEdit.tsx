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
import { MemoryOutlined } from "@mui/icons-material";
import { FieldsInterface, Form, useHandleCatch, useLoader } from "@arteneo/forge";
import { useNavigate, useParams } from "react-router-dom";
import CrudFieldset from "~app/fieldsets/CrudFieldset";
import Surface from "~app/components/Common/Surface";
import getEditSourceUploadFields from "~app/entities/Firmware/editSourceUpload";
import getEditSourceExternalUrlFields from "~app/entities/Firmware/editSourceExternalUrl";
import { FirmwareInterface } from "~app/entities/Firmware/definitions";
import { getFeatureName } from "~app/entities/Firmware/utilities";
import SurfaceTitle from "~app/components/Common/SurfaceTitle";

const FirmwareEdit = () => {
    const navigate = useNavigate();
    const { id } = useParams();
    const handleCatch = useHandleCatch();
    const { showLoader, hideLoader } = useLoader();

    const [firmware, setFirmware] = React.useState<undefined | FirmwareInterface>(undefined);

    React.useEffect(() => load(), []);

    const load = () => {
        showLoader();

        axios
            .get("/firmware/" + id)
            .then((response) => {
                setFirmware(response.data);
                hideLoader();
            })
            .catch((error) => {
                hideLoader();
                handleCatch(error);
            });
    };

    if (typeof firmware === "undefined") {
        return null;
    }

    const endpoint = "/firmware/" + firmware.id + "/source/" + firmware.sourceType.toLowerCase() + "/edit";

    let fields: FieldsInterface = {};

    if (firmware.sourceType === "upload") {
        fields = getEditSourceUploadFields();
    }

    if (firmware.sourceType === "externalUrl") {
        fields = getEditSourceExternalUrlFields();
    }

    return (
        <>
            <SurfaceTitle
                {...{
                    title: "route.title.firmware",
                    titleTo: "/firmware/list",
                    subtitle: "route.subtitle.detailsRepresentationDeviceTypeAndFeature",
                    subtitleVariables: {
                        representation: firmware.representation,
                        deviceType: firmware.deviceType?.name,
                        feature: getFeatureName(firmware.deviceType, firmware.feature),
                    },
                    hint: "route.hint.edit",
                    icon: <MemoryOutlined />,
                }}
            />
            <Surface>
                <Form
                    {...{
                        initialValues: firmware,
                        endpoint,
                        children: (
                            <CrudFieldset
                                {...{
                                    fields,
                                    backButtonProps: { onClick: () => navigate("/firmware/list") },
                                }}
                            />
                        ),
                        onSubmitSuccess: (defaultOnSubmitSuccess) => {
                            defaultOnSubmitSuccess();
                            navigate("/firmware/list");
                        },
                        fields,
                    }}
                />
            </Surface>
        </>
    );
};

export default FirmwareEdit;
