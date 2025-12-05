export class FeatureFlagService {
    private featureFlags: Record<string, boolean>;

    constructor() {
        this.featureFlags = {};
    }

    public enableFeature(feature: string): void {
        this.featureFlags[feature] = true;
    }

    public disableFeature(feature: string): void {
        this.featureFlags[feature] = false;
    }

    public isFeatureEnabled(feature: string): boolean {
        return !!this.featureFlags[feature];
    }

    public getAllFeatures(): Record<string, boolean> {
        return this.featureFlags;
    }
}