import { FeatureFlagService } from '../../src/services/featureFlagService';
import { ClientManager } from '../../src/services/clientManager';

describe('FeatureFlagService Integration Tests', () => {
    let featureFlagService: FeatureFlagService;
    let clientManager: ClientManager;

    beforeAll(() => {
        clientManager = new ClientManager();
        featureFlagService = new FeatureFlagService(clientManager);
    });

    it('should enable GPT-5 mini feature for all clients', async () => {
        const clients = await clientManager.getAllClients();
        await featureFlagService.enableFeatureForAll('gpt5-mini');

        for (const client of clients) {
            const updatedClient = await clientManager.getClientById(client.id);
            expect(updatedClient.features['gpt5-mini']).toBe(true);
        }
    });

    it('should disable GPT-5 mini feature for all clients', async () => {
        await featureFlagService.disableFeatureForAll('gpt5-mini');

        const clients = await clientManager.getAllClients();
        for (const client of clients) {
            const updatedClient = await clientManager.getClientById(client.id);
            expect(updatedClient.features['gpt5-mini']).toBe(false);
        }
    });
});