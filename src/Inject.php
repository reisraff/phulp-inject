<?php

namespace Phulp\Inject;

use Phulp\Collection;
use Phulp\DistFile;
use Phulp\PipeInterface;
use Phulp\Source;

class Inject implements PipeInterface
{
    /**
     * @var Collection
     */
    private $distFiles;

    /**
     * @var array
     */
    private $options = [
        'tagname' => 'inject',
        'starttag' => null,
        'endtag' => null,
        'filterFilename' => null,
    ];

    /**
     * @var array
     */
    private $prepared = [];

    /**
     * @var array
     */
    private $tags = [
        'html' => '(<!--\s*{{name}}:{{ext}}\s*-->)',
        'haml' => '(-#\s*{{name}}:{{ext}})',
        'jade' => '(\/\/-\s*{{name}}:{{ext}})',
        'pug' =>  '(\/\/-\s*{{name}}:{{ext}})',
        'jsx' =>  '({\/\*\s*{{name}}:{{ext}}\s*\*\/})',
        'slm' =>  '(\/\s*{{name}}:{{ext}})',
        'less' => '(\/\*\s*{{name}}:{{ext}}\s*\*\/)',
        'sass' => '(\/\*\s*{{name}}:{{ext}}\s*\*\/)',
        'scss' => '(\/\*\s*{{name}}:{{ext}}\s*\*\/)',
    ];

    /**
     * @var array
     */
    private $injections = [
        'html' => [
            'css' => '<link rel="stylesheet" href="{{filename}}">',
            'js' => '<script src="{{filename}}"></script>',
            'coffee' => '<script type="text/coffeescript" src="{{filename}}"></script>',
            'html' => '<link rel="import" href="{{filename}}">',
            'png' => '<img src="{{filename}}">',
            'gif' => '<img src="{{filename}}">',
            'jpg' => '<img src="{{filename}}">',
            'jpeg' => '<img src="{{filename}}">',
        ],
        'jsx' => [
            'css' => '<link rel="stylesheet" href="{{filename}}">',
            'js' => '<script src="{{filename}}" />',
            'coffee' => '<script type="text/coffeescript" src="{{filename}}" />',
            'html' => '<link rel="import" href="{{filename}}">',
            'png' => '<img src="{{filename}}" />',
            'gif' => '<img src="{{filename}}" />',
            'jpg' => '<img src="{{filename}}" />',
            'jpeg' => '<img src="{{filename}}" />',
        ],
        'jade' => [
            'css' => 'link(rel="stylesheet", href="{{filename}}")',
            'js' => 'script(src="{{filename}}")',
            'coffee' => 'script(type="text/coffeescript", src="{{filename}}")',
            'html' => 'link(rel="import", href="{{filename}}")',
            'png' => 'img(src="{{filename}}")',
            'gif' => 'img(src="{{filename}}")',
            'jpg' => 'img(src="{{filename}}")',
            'jpeg' => 'img(src="{{filename}}")',
        ],
        'pug' => [
            'css' => 'link(rel="stylesheet", href="{{filename}}")',
            'js' => 'script(src="{{filename}}")',
            'coffee' => 'script(type="text/coffeescript", src="{{filename}}")',
            'html' => 'link(rel="import", href="{{filename}}")',
            'png' => 'img(src="{{filename}}")',
            'gif' => 'img(src="{{filename}}")',
            'jpg' => 'img(src="{{filename}}")',
            'jpeg' => 'img(src="{{filename}}")',
        ],
        'slm' => [
            'css' => 'link rel="stylesheet" href="{{filename}}"',
            'js' => 'script src="{{filename}}"',
            'coffee' => 'script type="text/coffeescript" src="{{filename}}"',
            'html' => 'link rel="import" href="{{filename}}"',
            'png' => 'img src="{{filename}}"',
            'gif' => 'img src="{{filename}}"',
            'jpg' => 'img src="{{filename}}"',
            'jpeg' => 'img src="{{filename}}"',
        ],
        'haml' => [
            'css' => '%link{rel:"stylesheet", href:"{{filename}}"}',
            'js' => '%script{src:"{{filename}}"}',
            'coffee' => '%script{type:"text/coffeescript", src:"{{filename}}"}',
            'html' => '%link{rel:"import", href:"{{filename}}"}',
            'png' => '%img{src:"{{filename}}"}',
            'gif' => '%img{src:"{{filename}}"}',
            'jpg' => '%img{src:"{{filename}}"}',
            'jpeg' => '%img{src:"{{filename}}"}',
        ],
        'less' => [
            'css' => '@import "{{filename}}";',
            'less' => '@import "{{filename}}";',
        ],
        'scss' => [
            'css' => '@import "{{filename}}";',
            'scss' => '@import "{{filename}}";',
            'sass' => '@import "{{filename}}";',
        ],
        'sass' => [
            'css' => '@import "{{filename}}"',
            'sass' => '@import "{{filename}}"',
            'scss' => '@import "{{filename}}"',
        ],
    ];

    /**
     * @param Collection $distfiles,
     * @param array $options
     *
     * @throws \UnexpectedValueException
     */
    public function __construct(Collection $distFiles, array $options = [])
    {
        if ($distFiles->getType() !== DistFile::class) {
            throw new \UnexpectedValueException('Invalid Collection Type');
        }

        $this->distFiles = $distFiles;
        $this->options = array_merge($this->options, $options);
    }

    /**
     * @inheritdoc
     */
    public function execute(\Phulp\Source $src)
    {
        foreach ($this->distFiles as $distFile) {
            $this->prepare($distFile);
        }

        foreach ($src->getDistFiles() as $distFile) {
            $this->inject($distFile, $this->prepared);
        }
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function getExt($name)
    {
        return preg_match('/\./', $name) ? substr($name, strrpos($name, '.') + 1) : null;
    }

    /**
     * @param DistFile $distFile
     */
    private function prepare(DistFile $distFile)
    {
        if ($ext = $this->getExt($distFile->getDistpathname())) {
            $this->prepared[$ext][] = $distFile;
        }
    }

    /**
     * @param DistFile $distFile
     * @param array $prepared
     */
    private function inject(DistFile $distFile, array $prepared)
    {
        $distExt = $this->getExt($distFile->getDistpathname());

        if (isset($this->tags[$distExt])) {
            foreach ($prepared as $ext => $item) {
                foreach ($item as $file) {
                    $this->doInjection($distFile, $distExt, $file, $ext);
                }
            }
        }
    }

    /**
     * @param DistFile $distFile
     * @param string $distExt
     * @param DistFile $file
     * @param string $ext
     */
    private function doInjection(DistFile $distFile, $distExt, DistFile $file, $ext)
    {
        $content = $distFile->getContent();

        $regexBase = sprintf(
            '/%s(.*?)%s/s',
            $this->getInjectionPlaceholder($distExt),
            $this->getInjectionPlaceholder($distExt, false)
        );

        $regexExt = preg_replace('/{{ext}}/', $ext, $regexBase);
        $regexNull = preg_replace('/:{{ext}}/', null, $regexBase);
        $regex = null;

        if (preg_match($regexExt, $content)) {
            $regex = $regexExt;
        } elseif (preg_match($regexNull, $content)) {
            $regex = $regexNull;
        }

        if ($regex) {
            $distFile->setContent(
                preg_replace(
                    $regex,
                    '$1$2' . $this->getInjectionString($file, $ext, $distExt) . '$3',
                    $content
                )
            );
        }
    }

    /**
     * @param string $ext
     * @param boolean $start
     *
     * @return string
     */
    private function getInjectionPlaceholder($ext, $start = true)
    {
        if ($start && $this->options['starttag']) {
            return sprintf(
                '(%s)',
                preg_quote($this->options['starttag'])
            );
        }

        if (! $start && $this->options['endtag']) {
            return sprintf(
                '(%s)',
                preg_quote($this->options['endtag'])
            );
        }

        $tagname = $this->options['tagname'];
        $name = $start ? $tagname : 'end' . $tagname;

        $tag = $this->tags[$ext];
        $tag = preg_replace('/{{name}}/', $name, $tag);
        $tag = $start ? $tag : preg_replace('/:{{ext}}/', null, $tag);

        return $tag;
    }

    /**
     * @param DistFile $file
     * @param string $ext
     * @param string $distExt
     *
     * @return string
     */
    private function getInjectionString(DistFile $file, $ext, $distExt)
    {
        if (! isset($this->injections[$distExt][$ext])) {
            return null;
        }

        $tag = $this->injections[$distExt][$ext];

        $filename = $file->getDistpathname();

        $filter = $this->options['filterFilename'];
        if (is_callable($filter)) {
            $filename = $filter($filename);
        }

        return preg_replace('/{{filename}}/', $filename, $tag) . PHP_EOL;
    }
}
