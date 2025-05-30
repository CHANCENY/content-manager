# Content Manager

A simple, modular content management system designed for creating and managing digital content with ease.

![License](https://img.shields.io/badge/license-MIT-blue.svg)

## Overview

Content Manager is a PHP-based content management system that provides a comprehensive framework for building content-driven websites and applications. It features a modular architecture with clear separation of concerns, making it highly extensible and customizable to meet specific project requirements.

## Features

- **Modular Architecture**: Easily extend or replace components as needed
- **Authentication & Authorization**: Comprehensive user management with multiple authentication methods
- **Theming System**: Flexible theming capabilities using Twig templates
- **File Management**: Robust file handling with upload capabilities
- **Database Abstraction**: Simple yet powerful database operations through Medoo
- **Form Handling**: Streamlined form generation, validation, and processing
- **Internationalization**: Translation and localization support
- **Event-Driven Architecture**: Event subscribers for extensible, decoupled code
- **RESTful Data Sources**: Integration with external data sources
- **CLI Tools**: Command-line interface for administrative tasks

## System Requirements

- PHP 8.0 or higher
- PDO PHP Extension
- Fileinfo PHP Extension
- Readline PHP Extension
- Composer

## Installation

### Using Composer

```bash
composer create-project simp/content-manager your-project-name
cd your-project-name
```

### Manual Installation

```bash
git clone https://github.com/CHANCENY/content-manager.git
cd content-manager
composer install
```

### Development Environment

The project includes Lando configuration for local development:

```bash
lando start
```

## Project Structure

```
content-manager/
├── core/               # Core system components
│   ├── components/     # Specialized components
│   ├── lib/            # Core libraries
│   └── modules/        # Functional modules
├── public/             # Publicly accessible files
│   ├── module/         # Public modules
│   └── theme/          # Theme files
└── schema/             # Database schema definitions
```

## Core System Components

The core system serves as a linker for various essential functionalities:

- **Session Management**: Default and file-based session handling
- **Database Connectivity**: Database abstraction and operations
- **Filesystem Operations**: File handling and management
- **Authentication & Authorization**: User authentication and access control
- **Request & Response Handling**: HTTP request/response processing
- **Theming**: Theme rendering and management
- **Services**: Service container and dependency injection

## Configuration

Basic configuration can be adjusted through the core modules:

```php
// Example configuration
$config = \Simp\Core\modules\config\Config::getInstance();
$config->set('site_name', 'My Content Site');
```

## Usage Examples

### Creating a Basic Page

```php
// Example page creation
use Simp\Core\modules\structures\ContentType;

$page = new ContentType('page');
$page->setTitle('Welcome to Content Manager');
$page->setContent('This is my first page created with Content Manager.');
$page->save();
```

### User Authentication

```php
// Example authentication
use Simp\Core\modules\auth\Authentication;

$auth = Authentication::getInstance();
if ($auth->login($username, $password)) {
    echo "User authenticated successfully!";
}
```

## Extending the System

The modular architecture allows for easy extension:

```php
// Example custom module
namespace MyProject\CustomModule;

class MyModule {
    // Implementation
}
```

Register your custom module in the appropriate configuration file.

## API Documentation

Comprehensive API documentation is available in the code comments. You can generate documentation using tools like phpDocumentor.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Author

- **CHANCENY** - [GitHub](https://github.com/CHANCENY) - [nyasuluchance6@gmail.com](mailto:nyasuluchance6@gmail.com)

## Acknowledgments

- Symfony Components
- Twig Template Engine
- Medoo Database Framework
- All other open-source libraries used in this project
