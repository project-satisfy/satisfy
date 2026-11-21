<?php

namespace Playbloom\Exception;

/**
 * A webhook delivery that is deliberately not acted on. Answered 200, so the
 * provider's delivery log does not fill with failures that are correct.
 */
class WebhookSkippedException extends \RuntimeException
{
}
