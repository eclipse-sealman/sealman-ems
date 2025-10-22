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

import { createTheme } from "@mui/material";
import { lighten } from "@mui/system";

declare module "@mui/material/styles" {
    interface Palette {
        critical: Palette["primary"];
    }

    // allow configuration using `createTheme`
    interface PaletteOptions {
        critical?: PaletteOptions["primary"];
    }
}

// @babel-ignore-comment-in-output Update the Button's color prop options
declare module "@mui/material/Button" {
    interface ButtonPropsColorOverrides {
        critical: true;
    }
}

// @babel-ignore-comment-in-output Update the Button's color prop options
declare module "@mui/material/Chip" {
    interface ChipPropsColorOverrides {
        critical: true;
    }
}

let theme = createTheme({
    palette: {
        background: {
            default: "#f3f3f3",
        },
        primary: {
            main: "#1e3b70",
        },
        secondary: {
            main: "#40474d",
        },
        text: {
            primary: "#122e37",
        },
        critical: {
            main: "#FF0000",
            contrastText: "#fff",
        },
    },
    typography: {
        fontFamily: ["Source Sans Pro"].join(", "),
        fontSize: 16,
        fontWeightMedium: 600,
        h1: {
            fontSize: 24,
            fontWeight: 600,
        },
        h2: {
            fontSize: 22,
            fontWeight: 600,
        },
        h3: {
            fontSize: 20,
            fontWeight: 600,
        },
        h4: {
            fontSize: 18,
            fontWeight: 600,
        },
        h5: {
            fontWeight: 600,
        },
        h6: {
            fontWeight: 600,
        },
    },
    shape: {
        borderRadius: 15,
    },
    mixins: {
        toolbar: {
            minHeight: 60,
            "@media (min-width: 0px)": {
                "@media (orientation: landscape)": {
                    minHeight: 60,
                },
            },
            "@media (min-width: 600px)": {
                minHeight: 60,
            },
        },
    },
});

theme = createTheme(theme, {
    components: {
        MuiTooltip: {
            defaultProps: {
                placement: "right",
            },
        },
        MuiCssBaseline: {
            styleOverrides: {
                a: {
                    textDecoration: "none",
                    color: "inherit",
                    "&:hover": {
                        textDecoration: "underline",
                    },
                },
            },
        },
        MuiAutocomplete: {
            styleOverrides: {
                inputRoot: {
                    paddingTop: 4,
                    paddingBottom: 4,
                    borderRadius: 12,
                },
                popper: {
                    borderRadius: 12,
                    borderWidth: 1,
                    borderStyle: "solid",
                    borderColor: theme.palette.grey[400],
                },
                listbox: {
                    "& .MuiAutocomplete-option[aria-selected='true']": {
                        backgroundColor: "transparent",
                    },
                },
            },
        },
        MuiOutlinedInput: {
            styleOverrides: {
                root: {
                    borderRadius: 12,
                },
                input: {
                    paddingTop: 12,
                    paddingBottom: 12,
                    borderRadius: 12,
                },
                multiline: {
                    paddingTop: 12,
                    paddingBottom: 12,
                },
                inputMultiline: {
                    paddingTop: 0,
                    paddingBottom: 0,
                    borderRadius: 0,
                },
            },
        },
        MuiInputLabel: {
            styleOverrides: {
                root: {
                    transform: "translate(14px, 12px) scale(1)",
                },
                shrink: {
                    transform: "translate(14px, -9px) scale(0.75)",
                },
            },
        },
        MuiButton: {
            styleOverrides: {
                root: {
                    textTransform: "none",
                    boxShadow: "none",
                    fontWeight: 400,
                    "&:hover": {
                        boxShadow: "none",
                    },
                },
                sizeSmall: {
                    padding: "3px 15px",
                },
                sizeMedium: {
                    padding: "5px 20px",
                },
                startIcon: {
                    marginLeft: "-3px",
                    marginRight: "6px",
                },
                containedError: {
                    background: lighten(theme.palette.error.light, 0.88),
                    color: theme.palette.error.main,
                    "&:hover": {
                        background: lighten(theme.palette.error.light, 0.8),
                    },
                },
                containedWarning: {
                    background: lighten(theme.palette.warning.light, 0.88),
                    color: theme.palette.warning.main,
                    "&:hover": {
                        background: lighten(theme.palette.warning.light, 0.8),
                    },
                },
                containedInfo: {
                    background: lighten(theme.palette.info.light, 0.88),
                    color: theme.palette.info.main,
                    "&:hover": {
                        background: lighten(theme.palette.info.light, 0.8),
                    },
                },
                containedSuccess: {
                    background: lighten(theme.palette.success.light, 0.88),
                    color: theme.palette.success.main,
                    "&:hover": {
                        background: lighten(theme.palette.success.light, 0.8),
                    },
                },
                menuItem: {
                    display: "flex",
                    padding: "3px 12px",
                    justifyContent: "flex-start",
                    width: "100%",
                },
            },
        },
        MuiIconButton: {
            styleOverrides: {
                colorError: {
                    background: lighten(theme.palette.error.light, 0.88),
                    color: theme.palette.error.main,
                    "&:hover": {
                        background: lighten(theme.palette.error.light, 0.8),
                    },
                },
                colorWarning: {
                    background: lighten(theme.palette.warning.light, 0.88),
                    color: theme.palette.warning.main,
                    "&:hover": {
                        background: lighten(theme.palette.warning.light, 0.8),
                    },
                },
                colorInfo: {
                    background: lighten(theme.palette.info.light, 0.88),
                    color: theme.palette.info.main,
                    "&:hover": {
                        background: lighten(theme.palette.info.light, 0.8),
                    },
                },
                colorSuccess: {
                    background: lighten(theme.palette.success.light, 0.88),
                    color: theme.palette.success.main,
                    "&:hover": {
                        background: lighten(theme.palette.success.light, 0.8),
                    },
                },
            },
        },
        MuiChip: {
            styleOverrides: {
                sizeSmall: {
                    fontSize: 14,
                },
                colorError: {
                    background: lighten(theme.palette.error.light, 0.88),
                    borderColor: lighten(theme.palette.error.light, 0.88),
                    color: theme.palette.error.main,
                },
                colorWarning: {
                    background: lighten(theme.palette.warning.light, 0.88),
                    borderColor: lighten(theme.palette.warning.light, 0.88),
                    color: theme.palette.warning.main,
                },
                colorInfo: {
                    background: lighten(theme.palette.info.light, 0.88),
                    borderColor: lighten(theme.palette.info.light, 0.88),
                    color: theme.palette.info.main,
                },
                colorSuccess: {
                    background: lighten(theme.palette.success.light, 0.88),
                    borderColor: lighten(theme.palette.success.light, 0.88),
                    color: theme.palette.success.main,
                },
            },
        },
        MuiDialogActions: {
            styleOverrides: {
                root: {
                    paddingTop: 16,
                    paddingBottom: 16,
                    paddingLeft: 24,
                    paddingRight: 24,
                },
            },
        },
        MuiPaper: {
            defaultProps: {
                elevation: 0,
            },
            styleOverrides: {
                root: {
                    borderWidth: 1,
                    borderStyle: "solid",
                    borderColor: "#e9e9e9",
                },
            },
        },
        MuiDrawer: {
            styleOverrides: {
                paper: {
                    borderWidth: 0,
                },
            },
        },
        MuiSnackbar: {
            styleOverrides: {
                anchorOriginTopCenter: {
                    position: "absolute",
                    top: 0,
                    left: 0,
                    width: "100%",
                    transform: "none",
                    [theme.breakpoints.up("sm")]: {
                        top: 0,
                        left: 0,
                        width: "100%",
                        transform: "none",
                    },
                },
                root: {
                    zIndex: 10,
                    "& > .MuiPaper-root": {
                        borderTop: 0,
                        borderLeft: 0,
                        borderRight: 0,
                        borderRadius: 0,
                        "& > .MuiAlert-icon": {
                            alignItems: "center",
                        },
                    },
                },
            },
        },
        MuiTableCell: {
            styleOverrides: {
                root: {
                    borderBottomColor: theme.palette.grey[200],
                },
                head: {
                    borderBottomColor: theme.palette.grey[300],
                    "&.MuiTableCell-sizeMedium": {
                        paddingTop: 6,
                        paddingBottom: 6,
                        lineHeight: 1.43,
                    },
                    "&.MuiTableCell-sizeSmall": {
                        lineHeight: 1.43,
                    },
                },
                footer: {
                    borderBottomColor: theme.palette.grey[300],
                },
                sizeMedium: {
                    paddingTop: 12,
                    paddingBottom: 12,
                },
                sizeSmall: {
                    fontSize: 14,
                    paddingTop: 2,
                    paddingBottom: 2,
                    paddingLeft: 8,
                    paddingRight: 8,
                    borderWidth: 1,
                    borderStyle: "solid",
                    borderColor: theme.palette.grey[200],
                },
            },
        },
        MuiMenu: {
            styleOverrides: {
                list: {
                    paddingLeft: 8,
                    paddingRight: 8,
                },
            },
        },
        MuiMenuItem: {
            styleOverrides: {
                root: {
                    borderRadius: "12px",
                    "& .MuiListItemText-primary": {
                        fontSize: "1rem",
                    },
                    "& .MuiListItemIcon-root": {
                        // Make smaller left padding when icon is present
                        marginLeft: "-6px",
                    },
                },
            },
        },
        MuiListItemButton: {
            styleOverrides: {
                root: {
                    borderLeftWidth: 2,
                    borderLeftStyle: "solid",
                    borderLeftColor: "transparent",
                    "& > .MuiListItemText-root": {
                        "& > .MuiListItemText-primary": {
                            fontSize: 16,
                            fontWeight: 600,
                        },
                    },
                    "&:hover": {
                        backgroundColor: theme.palette.action.hover,
                    },
                    "&.Mui-selected": {
                        backgroundColor: "inherit",
                        borderLeftColor: theme.palette.primary.main,
                        "&:hover": {
                            backgroundColor: theme.palette.action.hover,
                        },
                        "& > .MuiListItemIcon-root": {
                            "& > .MuiSvgIcon-root": {
                                color: theme.palette.primary.main,
                            },
                        },
                        "& > .MuiListItemText-root": {
                            "& > .MuiListItemText-primary": {
                                color: theme.palette.primary.main,
                            },
                        },
                        "& > .MuiSvgIcon-root": {
                            color: theme.palette.primary.main,
                        },
                    },
                },
            },
        },
        MuiList: {
            styleOverrides: {
                root: {
                    "& .MuiList-root": {
                        paddingTop: 4,
                        paddingBottom: 4,
                        "& > .MuiListItem-root": {
                            paddingLeft: 46,
                            paddingTop: 4,
                            paddingBottom: 4,
                            "& > .MuiListItemButton-root": {
                                borderLeftWidth: 0,
                                borderRadius: 12,
                                paddingTop: 6,
                                paddingBottom: 6,
                                paddingLeft: 28,
                                paddingRight: 28,
                                "& > .MuiListItemText-root": {
                                    "& > .MuiListItemText-primary": {
                                        fontSize: 14,
                                        fontWeight: 400,
                                    },
                                },
                                "&.Mui-selected": {
                                    backgroundColor: "#f3f3f3",
                                    "&:hover": {
                                        backgroundColor: theme.palette.action.hover,
                                    },
                                    "& > .MuiListItemText-root": {
                                        "& > .MuiListItemText-primary": {
                                            color: theme.palette.text.primary,
                                            fontWeight: 600,
                                        },
                                    },
                                },
                            },
                        },
                    },
                },
            },
        },
    },
});

export default theme;
