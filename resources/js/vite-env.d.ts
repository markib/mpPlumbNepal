/// <reference types="vite/client" />

declare interface ImportMetaEnv {
  readonly VITE_APP_URL: string;
  readonly VITE_APP_NAME: string;
  readonly VITE_REVERB_APP_KEY: string;
  readonly VITE_REVERB_HOST: string;
  readonly VITE_REVERB_PORT: string;
  readonly [key: string]: string | undefined;
}

declare interface ImportMeta {
  readonly env: ImportMetaEnv;
}
