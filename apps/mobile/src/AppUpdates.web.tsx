import React from 'react';
import { WebReleaseBanner, WebReleaseInfo } from './WebInstall';
export function AppUpdatesProvider({children}:{children:React.ReactNode}){return <>{children}</>;}
export const ReleaseBanner=WebReleaseBanner;
export const ReleaseInfo=WebReleaseInfo;
