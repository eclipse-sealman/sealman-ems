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
import { DeviceConfigurationTypeInterface } from "~app/entities/DeviceType/definitions";

type DeviceTypeContextProps = DeviceConfigurationTypeInterface;

interface DeviceTypeProviderProps {
    deviceType: DeviceConfigurationTypeInterface;
    children: React.ReactNode;
}

const contextInitial = {} as DeviceConfigurationTypeInterface;

const DeviceTypeContext = React.createContext<DeviceTypeContextProps>(contextInitial);

const DeviceTypeProvider = ({ deviceType, children }: DeviceTypeProviderProps) => {
    return <DeviceTypeContext.Provider value={deviceType}>{children}</DeviceTypeContext.Provider>;
};

const useDeviceType = (): DeviceTypeContextProps => React.useContext(DeviceTypeContext);

export { DeviceTypeContext, DeviceTypeContextProps, DeviceTypeProvider, DeviceTypeProviderProps, useDeviceType };
