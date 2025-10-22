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
import { ExpandMoreOutlined } from "@mui/icons-material";
import { bindMenu, bindTrigger, usePopupState } from "material-ui-popup-state/hooks";
import ButtonChildExpand from "~app/components/Common/ButtonChildExpand";
import ResultButtonChildExpand from "~app/components/Common/ResultButtonChildExpand";

interface ButtonExpandProps extends Omit<ButtonProps, "children"> {
    children: React.ReactNode;
    // eslint-disable-next-line
    childrenProps?: any;
    keepOpened?: boolean;
}

const ButtonExpand = ({ children, childrenProps, keepOpened = false, ...props }: ButtonExpandProps) => {
    const popupState = usePopupState({ variant: "popper" });

    const renderChild = (child: React.ReactElement, key: number) => {
        // Quite brutal solution. Adjust when better alternative is found
        const isChildExpand = child.type === ButtonChildExpand || child.type === ResultButtonChildExpand ? true : false;

        // eslint-disable-next-line
        const childProps: any = {
            // This actually works without extending MUI theme. There are styles added for "muiItem" variant
            variant: "menuItem",
            startIcon: null,
            endIcon: null,
            ...childrenProps,
        };

        if (isChildExpand) {
            childProps["parentPopupState"] = popupState;
        }

        return (
            <Box key={key} {...{ onClick: keepOpened || isChildExpand ? undefined : popupState.close }}>
                {/* eslint-disable-next-line */}
                {React.cloneElement(child as React.ReactElement<any>, childProps)}
            </Box>
        );
    };

    return (
        <>
            <Button
                {...{
                    variant: "contained",
                    color: "info",
                    size: "small",
                    endIcon: <ExpandMoreOutlined />,
                    ...bindTrigger(popupState),
                    ...props,
                }}
            />
            {/* keepMounted: true is crucial to allow buttons in the DOM and allow them to i.e. show dialogs */}
            <Menu {...{ keepMounted: true, ...bindMenu(popupState) }}>
                {React.Children.toArray(children).map((child, key) => renderChild(child as React.ReactElement, key))}
            </Menu>
        </>
    );
};

export default ButtonExpand;
export { ButtonExpandProps };
