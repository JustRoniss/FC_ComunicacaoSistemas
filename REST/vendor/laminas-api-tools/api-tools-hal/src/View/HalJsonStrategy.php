<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\Hal\View;

use Laminas\ApiTools\ApiProblem\View\ApiProblemModel;
use Laminas\View\Model\ModelInterface;
use Laminas\View\Strategy\JsonStrategy;
use Laminas\View\ViewEvent;

use function is_string;

/**
 * Extension of the JSON strategy to handle the HalJsonModel and provide
 * a Content-Type header appropriate to the response it describes.
 *
 * This will give the following content types:
 *
 * - application/hal+json for a result that contains HAL-compliant links
 * - application/json for all other responses
 */
class HalJsonStrategy extends JsonStrategy
{
    /** @var string */
    protected $contentType = 'application/json';

    public function __construct(HalJsonRenderer $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * Detect if we should use the HalJsonRenderer based on model type.
     *
     * @return null|HalJsonRenderer
     */
    public function selectRenderer(ViewEvent $e)
    {
        $model = $e->getModel();

        if (! $model instanceof HalJsonModel) {
            // unrecognized model; do nothing
            return;
        }

        // JsonModel found
        $this->renderer->setViewEvent($e);

        return $this->renderer;
    }

    /**
     * Inject the response
     *
     * Injects the response with the rendered content, and sets the content
     * type based on the detection that occurred during renderer selection.
     */
    public function injectResponse(ViewEvent $e)
    {
        $renderer = $e->getRenderer();
        if ($renderer !== $this->renderer) {
            // Discovered renderer is not ours; do nothing
            return;
        }

        $result = $e->getResult();
        if (! is_string($result)) {
            // We don't have a string, and thus, no JSON
            return;
        }

        $model    = $e->getModel();
        $response = $e->getResponse();
        $response->setContent($result);

        $headers = $response->getHeaders();
        $headers->addHeaderLine(
            'content-type',
            $this->getContentTypeFromModel($model)
        );
    }

    /**
     * Determine the response content-type to return based on the view model.
     *
     * @param ApiProblemModel|HalJsonModel|ModelInterface $model
     * @return string The content-type to use.
     */
    private function getContentTypeFromModel($model)
    {
        if ($model instanceof ApiProblemModel) {
            return 'application/problem+json';
        }

        if (
            $model instanceof HalJsonModel
            && ($model->isCollection() || $model->isEntity())
        ) {
            return 'application/hal+json';
        }

        return $this->contentType;
    }
}
