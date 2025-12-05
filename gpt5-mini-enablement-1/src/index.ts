import { ClientManager } from './services/clientManager';
import { FeatureFlagService } from './services/featureFlagService';
import { enableGpt5Mini } from './commands/enableGpt5Mini';
import { defaultConfig } from './config/default';

// Initialize configuration
const config = defaultConfig;

// Initialize services
const clientManager = new ClientManager(config);
const featureFlagService = new FeatureFlagService(config);

// Command handling
const main = async () => {
    try {
        await enableGpt5Mini(clientManager, featureFlagService);
        console.log('GPT-5 mini feature enabled for all clients.');
    } catch (error) {
        console.error('Error enabling GPT-5 mini feature:', error);
    }
};

// Start the application
main();