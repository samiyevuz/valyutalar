export default {
  apiKey: process.env.API_KEY || 'your-default-api-key',
  featureFlags: {
    gpt5MiniEnabled: false,
  },
  clientSettings: {
    defaultClientLimit: 100,
  },
};