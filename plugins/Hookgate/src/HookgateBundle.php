<?php

namespace Playbloom\Plugin\Hookgate;

use Playbloom\Plugin\AbstractPlugin;

/**
 * For GitLab system hooks, which fire for every project on an instance:
 *
 *  - a registration gate: a repository is auto-added only if some branch or
 *    tag has a composer.json declaring a type the catalogue publishes;
 *  - auto-require: a tag pushed to a package not yet published adds it to
 *    satis.json's require list, so pushing the tag is all publishing takes.
 */
class HookgateBundle extends AbstractPlugin
{
}
