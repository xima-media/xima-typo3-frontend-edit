# Contributing

Thank you for considering contributing to this project! Every contribution is welcome and helps improve the quality of the project. To ensure a smooth process and maintain high code quality, please follow the steps below.

## Requirements

- [DDEV](https://ddev.readthedocs.io/en/stable/)

## Preparation

```bash
# Clone repository
git clone https://github.com/xima-media/xima-typo3-frontend-edit.git
cd xima-typo3-frontend-edit

# Start the project with DDEV
ddev start

# Install dependencies
ddev composer install
```

## Run linters

```bash
# All linters
ddev cgl lint

# Specific linters
ddev cgl lint:composer
ddev cgl lint:editorconfig
ddev cgl lint:language
ddev cgl lint:php
ddev cgl lint:typoscript
ddev cgl lint:yaml

# Fix all CGL issues
ddev cgl fix

# Fix specific CGL issues
ddev cgl fix:composer
ddev cgl fix:editorconfig
ddev cgl fix:php
```

## Run static code analysis

```bash
# All static code analyzers
ddev cgl sca

# Specific static code analyzers
ddev cgl sca:php
```

## Run tests

```bash
# All tests
ddev composer test

# All tests with code coverage
ddev composer test:coverage
```

## TYPO3 Setup

For testing the extension, you need to set up the TYPO3 instances.

```bash
# Install all TYPO3 versions, which are supported by the extension
ddev install all

# Or install specific TYPO3 versions
ddev install 11
ddev install 12
ddev install 13

# Open the overview page
ddev launch

# Run TYPO3 specific commands
ddev 12 typo3 cache:flush
ddev 13 composer install
ddev all typo3 database:updateschema
```

## Submit a pull request

After completing your work, **open a pull request** and provide a description of your changes. Ideally, your PR should reference an issue that explains the problem you are addressing.

All mentioned code quality tools will run automatically on every pull request. For more details, see the relevant [workflows][1].

[1]: .github/workflows
