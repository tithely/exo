<?php

namespace Exo;

use Exo\Operation\AbstractOperation;

abstract class AbstractContextEnabledMigration implements MigrationInterface
{
    /**
     * @var string|null
     */
    protected $body;

    /**
     * @var array
     */
    private $expectedContext = [];

    /**
     * @var array
     */
    private $context = [];

    /**
     * Pushes a new add column operation.
     *
     * @param string $body
     * @return AbstractContextEnabledMigration
     */
    abstract public function withBody(string $body);

    /**
     * Returns the table operation.
     *
     * @param array $context
     * @return AbstractOperation
     */
    abstract public function getOperation(array $context = []);

    /**
     * Returns the operation body.
     *
     * @return string|null
     */
    public function getBody(): ?string
    {
        return $this->body;
    }

    /**
     * Returns the AbstractContextEnabledMigration with the expected context passed in.
     *
     * @param array $expectedContext
     * @return AbstractContextEnabledMigration
     */
    public function withExpectedContext(array $expectedContext)
    {
        $this->expectedContext = $expectedContext;

        return $this;
    }

    /**
     * Returns the expected context of the migration.
     *
     * @return array $expectedContext
     */
    public function getExpectedContext(): array
    {
        return $this->expectedContext;
    }

    /**
     * Returns the operation context.
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Sets the operation context to context array passed in.
     *
     * @param array $context
     * @return void
     */
    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    /**
     * Validates this operations context against the a migrations expected context.
     *
     * @return void
     * @throws InvalidMigrationContextException
     */
    public function validateContext(): void
    {
        if (empty($this->getExpectedContext())) {
            return;
        }
        $operationContext = $this->getContext();

        foreach ($this->getExpectedContext() as $expectedContextKey) {
            if (!array_key_exists($expectedContextKey, $operationContext)) {
                throw new InvalidMigrationContextException($expectedContextKey);
            }
        }
    }

    /**
     * Returns the Migration with the current context applied to the body.
     *
     * @return string|null
     * @throws MigrationRenderException
     */
    public function renderBody(): ?string
    {
        if (empty($this->getBody()) || empty($this->getExpectedContext())) {
            return $this->getBody();
        }

        try {
            $renderer = new \Twig\Environment(
                new \Twig\Loader\ArrayLoader([
                    'migration' => $this->getBody()
                ])
            );

            $this->body = $renderer->render(
                'migration',
                $this->getContext()
            );

            return $this->getBody();

        } catch (\Exception $e) {
            throw new MigrationRenderException($e->getMessage());
        }
    }
}
