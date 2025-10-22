# Frontend application (WebUI)

## Introduction

Frotend application is located in `app/` folder.

**Note!** It also holds translation file used by backend application `app/src/translations/messages.en.json`.

Compilation directory is set to `public/app/`. Main file is `main.js` which is loaded by Twig (which is translated to HTML) when accessing the application through backend.

Application manages packages needed for development and deployment with `npm` (version 10.x) which requires `Node.js` (version 20.x).

Please follow `npm` documentation:

[Downloading and installing Node.js and npm](https://docs.npmjs.com/downloading-and-installing-node-js-and-npm)

**Important** All commands presented in this section are run from `app/` folder. Consider `app/` folder as a root folder when working with frontend application.

## Configuration

Please refer to `.env.dev` or `.env.prod` file (depending on the environment) for additional application configuration (i.e. API url prefix).

## Development compilation

Install needed packages by using following command:

```
npm install
```

Compile application in development mode by using following command:

```
npm run watch
```

This command will watch files and recompile whenever they change.

## Production compilation

Install needed packages by using following command:

```
npm install
```

Compile application in production mode by using following command:

```
npm run build
```
