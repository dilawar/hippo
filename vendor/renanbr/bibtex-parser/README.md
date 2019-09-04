<h1 align="center">PHP BibTeX Parser 2.x</h1>
<p align="center">
    This is a
    <a href="https://www.ctan.org/pkg/bibtex">BibTeX</a>
    parser written in
    <a href="https://php.net">PHP</a>.
</p>
<p align="center">
    <a href="https://www.ctan.org/pkg/bibtex">
        <img src="https://upload.wikimedia.org/wikipedia/commons/3/30/BibTeX_logo.svg" height="83" alt="BibTeX logo">
    </a>
    <a href="https://php.net">
        <img src="https://upload.wikimedia.org/wikipedia/commons/2/27/PHP-logo.svg" height="83" alt="PHP logo">
    </a>
</p>

[![Build Status](https://travis-ci.org/renanbr/bibtex-parser.svg?branch=master)](https://travis-ci.org/renanbr/bibtex-parser)
[![codecov](https://codecov.io/gh/renanbr/bibtex-parser/branch/master/graph/badge.svg)](https://codecov.io/gh/renanbr/bibtex-parser)

You are browsing the documentation of **BibTeX Parser 2.x**, the latest version.

[Documentation for version 1.x is available here](https://github.com/renanbr/bibtex-parser/blob/1.x/README.md).

## Table of contents

* [Installing](#installing)
* [Usage](#usage)
* [Vocabulary](#vocabulary)
* [Processors](#processors)
   * [Tag name case](#tag-name-case)
   * [Authors and editors](#authors-and-editors)
   * [Keywords](#keywords)
   * [LaTeX to unicode](#latex-to-unicode)
   * [Custom](#custom)
* [Handling errors](#handling-errors)
* [Advanced usage](#advanced-usage)

## Installing

```bash
composer require renanbr/bibtex-parser ^2
```

## Usage

```php
use RenanBr\BibTexParser\Listener;
use RenanBr\BibTexParser\Parser;

require 'vendor/autoload.php';

$bibtex = <<<BIBTEX
@article{einstein1916relativity,
  title={Relativity: The Special and General Theory},
  author={Einstein, Albert},
  year={1916}
}
BIBTEX;

$parser = new Parser();          // Create a Parser
$listener = new Listener();      // Create and configure a Listener
$parser->addListener($listener); // Attach the Listener to the Parser
$parser->parseString($bibtex);   // or parseFile('/path/to/file.bib')
$entries = $listener->export();  // Get processed data from the Listener

print_r($entries);
```

This will output:

```
Array
(
    [0] => Array
        (
            [type] => article
            [citation-key] => einstein1916relativity
            [title] => Relativity: The Special and General Theory
            [author] => Einstein, Albert
            [year] => 1916
        )
)
```

## Vocabulary

BibTeX is all about "entry", "tag's name" and "tag's content".

> A BibTeX **entry** consists of the type (the word after @), a citation-key and a number of tags which define various characteristics of the specific BibTeX entry. (...) A BibTeX **tag** is specified by its **name** followed by an equals-sign and the **content**.

Source: http://www.bibtex.org/Format/

Note: This library considers "type" and "citation-key" as tags. This behavior can be changed implementing your own Listener (more info at the end of this document).

## Processors

`Processor` is a [callable] that receives an entry as argument and returns a modified entry.

This library contains three main parts:

- `Parser` class, responsible for detecting units inside a BibTeX input;
- `Listener` class, responsible for gathering units and transforming them into a list of entries;
- `Processor` classes, responsible for manipulating entries.

Despite you can't configure the `Parser`, you can append as many `Processor` as you want to the `Listener` through `Listener::addProcessor()` before exporting the contents. Be aware that `Listener` provides, by default, these features:

- Found entries are reachable through `Listener::export()` method;
- [Tag content concatenation](http://www.bibtex.org/Format/);
    - e.g. `hello # " world"` tag's content will generate `hello world` [string]
- [Tag content abbreviation handling](http://www.bibtex.org/Format/);
    - e.g. `@string{foo="bar"} @misc{bar=foo}` will make `$entries[1]['bar']` assume `bar` as value
- Publication's type is exposed as `type` tag;
- Citation key is exposed as `citation-key` tag;
- Original entry text is exposed as `_original` tag.

This project is shipped with some useful processors.

### Tag name case

In BibTeX the tag's names aren't case-sensitive. This library exposes entries as [array], in which keys are case-sensitive. To avoid this misunderstanding, you can force the tags' name character case using `TagNameCaseProcessor`.

```php
use RenanBr\BibTexParser\Processor\TagNameCaseProcessor;

$listener->addProcessor(new TagNameCaseProcessor(CASE_UPPER)); // or CASE_LOWER
```

```bib
@article{
  title={BibTeX rocks}
}
```

```
Array
(
    [0] => Array
        (
            [TYPE] => article
            [TITLE] => BibTeX rocks
        )
)
```

### Authors and editors

BibTeX recognizes four parts of an author's name: First Von Last Jr. If you would like to parse the `author` and `editor` tags included in your entries, you can use the `NamesProcessor` class.

```php
use RenanBr\BibTexParser\Processor\NamesProcessor;

$listener->addProcessor(new NamesProcessor());
```

```bib
@article{
  title={Relativity: The Special and General Theory},
  author={Einstein, Albert}
}
```

```
Array
(
    [0] => Array
        (
            [type] => article
            [title] => Relativity: The Special and General Theory
            [author] => Array
                (
                    [0] => Array
                        (
                            [first] => Albert
                            [von] =>
                            [last] => Einstein
                            [jr] =>
                        )
                )
        )
)
```

### Keywords

The `keywords` tag contains a list of expressions represented as [string], you might want to read them as an [array] instead.

```php
use RenanBr\BibTexParser\Processor\KeywordsProcessor;

$listener->addProcessor(new KeywordsProcessor());
```

```bib
@misc{
  title={The End of Theory: The Data Deluge Makes the Scientific Method Obsolete},
  keywords={big data, data deluge, scientific method}
}
```

```
Array
(
    [0] => Array
        (
            [type] => misc
            [title] => The End of Theory: The Data Deluge Makes the Scientific Method Obsolete
            [keywords] => Array
                (
                    [0] => big data
                    [1] => data deluge
                    [2] => scientific method
                )
        )
)
```

### LaTeX to unicode

BibTeX files store LaTeX contents. You might want to read them as unicode instead. The `LatexToUnicodeProcessor` class solves this problem, but before adding the processor to the listener you must:

- [install Pandoc](http://pandoc.org/installing.html) in your system; and
- add [ryakad/pandoc-php](https://github.com/ryakad/pandoc-php) as a dependency of your project.

```php
use RenanBr\BibTexParser\Processor\LatexToUnicodeProcessor;

$listener->addProcessor(new LatexToUnicodeProcessor());
```

```bib
@article{
  title={Caf\\'{e}s and bars}
}
```

```
Array
(
    [0] => Array
        (
            [type] => article
            [title] => Cafés and bars
        )
)
```

Note: Order matters, add this processor as the last.

### Custom

The `Listener::addProcessor()` method expects a [callable] as argument. In the example shown below, we append the text `with laser` to the `title` tags for all entries.

```php
$listener->addProcessor(function (array $entry) {
    $entry['title'] .= ' with laser';
    return $entry;
});
```

```
@article{
  title={BibTeX rocks}
}
```

```
Array
(
    [0] => Array
        (
            [type] => article
            [title] => BibTeX rocks with laser
        )
)
```

## Handling errors

This library throws two types of exception: `ParserException` and `ProcessorException`. The first one may happen during the data extraction. When it occurs it probably means the parsed BibTeX isn't valid. The second exception may be throwed during the data processing. When it occurs it means the listener's processors can't handle properly the data found. Both implement `ExceptionInterface`.

```php
use RenanBr\BibTexParser\Exception\ExceptionInterface;
use RenanBr\BibTexParser\Exception\ParserException;
use RenanBr\BibTexParser\Exception\ProcessorException;

try {
    // ... parser and listener configuration

    $parser->parseFile('/path/to/file.bib');
    $entries = $listener->export();
} catch (ParserException $exception) {
    // The BibTeX isn't valid
} catch (ProcessorException $exception) {
    // Listener's processors aren't able to handle data found
} catch (ExceptionInterface $exception) {
    // Alternatively, you can use this exception to catch all of them at once
}
```

## Advanced usage

The core of this library is constituted of these classes:

- `RenanBr\BibTexParser\Parser`: responsible for detecting units inside a BibTeX input;
- `RenanBr\BibTexParser\ListenerInterface`: responsible for treating units found.

You can attach listeners to the parser through `Parser::addListener()`. The parser is able to detect BibTeX units, such as "type", "tag's name", "tag's content". As the parser finds an unit, listeners are triggered.

You can code your own listener! All you have to do is handle units.

```php
interface RenanBr\BibTexParser\ListenerInterface
{
    /**
     * Called when an unit is found.
     *
     * @param string $text    The original content of the unit found.
     *                        Escape character will not be sent.
     * @param string $type    The type of unit found.
     *                        It can assume one of Parser's constant value.
     * @param array  $context Contains details of the unit found.
     */
    public function bibTexUnitFound($text, $type, array $context);
}
```

`$type` may assume one of these values:

- `Parser::TYPE`
- `Parser::CITATION_KEY`
- `Parser::TAG_NAME`
- `Parser::RAW_TAG_CONTENT`
- `Parser::BRACED_TAG_CONTENT`
- `Parser::QUOTED_TAG_CONTENT`
- `Parser::ENTRY`

`$context` is an array with these keys:

- `offset` contains the `$text`'s beginning position.
  It may be useful, for example, to [seek on a file pointer](https://php.net/fseek);
- `length` contains the original `$text`'s length.
  It may differ from string length sent to the listener because may there are escaped characters.

[array]: https://php.net/manual/language.types.array.php
[callable]: https://php.net/manual/en/language.types.callable.php
[string]: https://php.net/manual/language.types.string.php
