# Contributing to BibTex Parser

## Some ways to contribute

- Reporting a issue:
    - [Make a question](https://github.com/renanbr/bibtex-parser/issues/new?title=Type%20your%20question%20here&labels=question)
    - [Report a bug](https://github.com/renanbr/bibtex-parser/issues/new?title=Describe%20the%20problem%20you%27re%20facing%20here&labels=bug)
- Improving or fixing the documentation [editing a file directly on GitHub](https://help.github.com/articles/editing-files-in-another-user-s-repository/);
- Coding (next section).

## An usual developer's journey

1. [Fork the repository](https://help.github.com/articles/fork-a-repo/);
2. Install the project;
    - [Create a local clone of your fork](https://help.github.com/articles/fork-a-repo/#step-2-create-a-local-clone-of-your-fork)
    - [Configure Git to sync your fork with the original repository](https://help.github.com/articles/fork-a-repo/#step-3-configure-git-to-sync-your-fork-with-the-original-spoon-knife-repository)
    - Install dependencies using [Composer]: `composer install`
3. Code;
4. [Make a pull request](https://help.github.com/articles/creating-a-pull-request-from-a-fork/).

### Tests

This project uses [PHPUnit] as testing framework. Tests configuration is store in the `phpunit.xml` file. To run tests you can use the following command:

```bash
vendor/bin/phpunit --coverage-html=build/coverage
```

### Coding standards

This project uses [PHP Coding Standards Fixer] to check and fix rules violations. The coding standards are defined in the `.php_cs` file. Related commands:

```bash
# For checking violations
php-cs-fixer fix --dry-run -vvv

# For fixing violations
php-cs-fixer fix
```

[Composer]: https://getcomposer.org
[Git]: https://git-scm.com
[PHP Coding Standards Fixer]: http://cs.sensiolabs.org
[PHPUnit]: https://phpunit.de
[Xdebug]: https://xdebug.org
