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
import { Button, ButtonProps } from "@arteneo/forge";
import { Box, Menu } from "@mui/material";
import { KeyboardArrowRightOutlined } from "@mui/icons-material";
import { PopupState, bindMenu, bindTrigger, usePopupState } from "material-ui-popup-state/hooks";

interface ButtonChildExpandProps extends Omit<ButtonProps, "children"> {
    children: React.ReactNode;
    parentPopupState?: PopupState;
    // eslint-disable-next-line
    childrenProps?: any;
    keepOpened?: boolean;
}

const ButtonChildExpand = ({
    children,
    parentPopupState,
    childrenProps,
    keepOpened = false,
    ...props
}: ButtonChildExpandProps) => {
    const popupState = usePopupState({ variant: "popper" });

    let onClose: undefined | (() => void) = undefined;
    if (!keepOpened) {
        onClose = () => {
            popupState.close();

            if (parentPopupState) {
                parentPopupState.close();
            }
        };
    }

    return (
        <>
            <Button
                {...{
                    variant: "contained",
                    color: "info",
                    size: "small",
                    ...bindTrigger(popupState),
                    ...props,
                    endIcon: <KeyboardArrowRightOutlined />,
                }}
            />
            {/* keepMounted: true is crucial to allow buttons in the DOM and allow them to i.e. show dialogs */}
            <Menu
                {...{
                    keepMounted: true,
                    ...bindMenu(popupState),
                    anchorOrigin: {
                        vertical: "top",
                        horizontal: "right",
                    },
                    transformOrigin: {
                        vertical: "top",
                        horizontal: "left",
                    },
                }}
            >
                {React.Children.toArray(children).map((child, key) => (
                    <Box key={key} {...{ onClick: onClose }}>
                        {/* eslint-disable-next-line */}
                        {React.cloneElement(child as React.ReactElement<any>, {
                            // This actually works without extending MUI theme. There are styles added for "muiItem" variant
                            variant: "menuItem",
                            startIcon: null,
                            endIcon: null,
                            ...childrenProps,
                        })}
                    </Box>
                ))}
            </Menu>
        </>
    );
};

export default ButtonChildExpand;
export { ButtonChildExpandProps };
