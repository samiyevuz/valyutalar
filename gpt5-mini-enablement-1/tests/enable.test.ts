import { enableGpt5Mini } from '../src/commands/enableGpt5Mini';
import { ClientManager } from '../src/services/clientManager';
import { FeatureFlagService } from '../src/services/featureFlagService';

jest.mock('../src/services/clientManager');
jest.mock('../src/services/featureFlagService');

describe('enableGpt5Mini', () => {
    let clientManager: ClientManager;
    let featureFlagService: FeatureFlagService;

    beforeEach(() => {
        clientManager = new ClientManager();
        featureFlagService = new FeatureFlagService();
    });

    it('should enable GPT-5 mini for all clients', async () => {
        const mockClients = [{ id: 1 }, { id: 2 }];
        clientManager.getAllClients = jest.fn().mockResolvedValue(mockClients);
        featureFlagService.enableFeatureForClients = jest.fn().mockResolvedValue(true);

        await enableGpt5Mini(clientManager, featureFlagService);

        expect(clientManager.getAllClients).toHaveBeenCalled();
        expect(featureFlagService.enableFeatureForClients).toHaveBeenCalledWith(mockClients, 'gpt5-mini');
    });

    it('should handle errors when enabling feature', async () => {
        clientManager.getAllClients = jest.fn().mockRejectedValue(new Error('Failed to fetch clients'));

        await expect(enableGpt5Mini(clientManager, featureFlagService)).rejects.toThrow('Failed to fetch clients');
    });
});