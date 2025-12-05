# gpt5-mini-enablement

This project enables the GPT-5 mini feature for all clients. It provides a command-line interface to manage feature flags and client configurations.

## Table of Contents

- [Installation](#installation)
- [Usage](#usage)
- [Configuration](#configuration)
- [Testing](#testing)
- [Scripts](#scripts)
- [License](#license)

## Installation

1. Clone the repository:
   ```
   git clone https://github.com/yourusername/gpt5-mini-enablement.git
   ```
2. Navigate to the project directory:
   ```
   cd gpt5-mini-enablement
   ```
3. Install the dependencies:
   ```
   npm install
   ```

## Usage

To enable the GPT-5 mini feature for all clients, run the following command:
```
npm run enable-gpt5-mini
```

## Configuration

Configuration settings can be found in the `src/config/default.ts` file. Environment-specific configurations are located in the `config` directory.

## Testing

To run the tests, use the following command:
```
npm test
```

## Scripts

- `deploy.sh`: Deploys the application to the production environment.
- `rollback.sh`: Rolls back the application to a previous version.

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.