<?php

namespace Playbloom\Exception;

/**
 * satis.json changed on disk after the copy being saved was read from it.
 */
class ConfigurationChangedException extends \RuntimeException
{
}
