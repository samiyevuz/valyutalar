export const enableGpt5Mini = async (clientManager, featureFlagService) => {
    try {
        const clients = await clientManager.getAllClients();
        
        for (const client of clients) {
            await featureFlagService.enableFeature(client.id, 'gpt5-mini');
        }

        console.log('GPT-5 mini feature has been enabled for all clients.');
    } catch (error) {
        console.error('Error enabling GPT-5 mini feature:', error);
    }
};