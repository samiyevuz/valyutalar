export class ClientManager {
    private clients: Map<string, any>;

    constructor() {
        this.clients = new Map();
    }

    public addClient(clientId: string, clientData: any): void {
        this.clients.set(clientId, clientData);
    }

    public getClient(clientId: string): any | undefined {
        return this.clients.get(clientId);
    }

    public updateClientFeatureFlag(clientId: string, feature: string, enabled: boolean): void {
        const client = this.getClient(clientId);
        if (client) {
            client.featureFlags[feature] = enabled;
        }
    }

    public getAllClients(): Array<any> {
        return Array.from(this.clients.values());
    }
}