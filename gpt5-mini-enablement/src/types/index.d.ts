export interface Client {
    id: string;
    name: string;
    email: string;
    featureFlags: Record<string, boolean>;
}

export interface FeatureFlag {
    name: string;
    enabled: boolean;
    description?: string;
}

export interface Config {
    apiKey: string;
    featureFlags: Record<string, boolean>;
}