<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

namespace Contao\Bundle\CoreBundle\Autoload;

/**
 * Handles a Contao legacy bundle
 *
 * @author Leo Feyer <https://contao.org>
 */
class LegacyBundle implements BundleInterface
{
    /**
     * @var string
     */
    protected $class;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $replace = [];

    /**
     * @var array
     */
    protected $environments = ['all'];

    /**
     * @var array
     */
    protected $loadAfter = [];

    /**
     * Constructor
     *
     * @param string $name    The module name
     * @param array  $options The bundle options
     */
    public function __construct($name, array $options)
    {
        $this->name = $name;

        if (!empty($options['load-after'])) {
            $this->setLoadAfter($options['load-after']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * {@inheritdoc}
     */
    public function setClass($class)
    {
        $this->class = $class;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getReplace()
    {
        return $this->replace;
    }

    /**
     * {@inheritdoc}
     */
    public function setReplace(array $replace)
    {
        $this->replace = $replace;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getEnvironments()
    {
        return $this->environments;
    }

    /**
     * {@inheritdoc}
     */
    public function setEnvironments(array $environments)
    {
        $this->environments = $environments;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLoadAfter()
    {
        return $this->loadAfter;
    }

    /**
     * {@inheritdoc}
     */
    public function setLoadAfter(array $loadAfter)
    {
        $this->loadAfter = $loadAfter;

        return $this;
    }
}