<?php

declare(strict_types=1);

namespace Gene\EncryptionKeyManager\Model;

use Magento\Config\Model\Placeholder\PlaceholderFactory;
use Magento\Config\Model\Placeholder\PlaceholderInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\PhpEnvironment\RequestFactory;

class ReEncryptCloudEnvKeysCommand
{
    /**
     * @var PlaceholderInterface
     */
    private PlaceholderInterface $placeholder;

    /**
     * Constructor
     *
     * @param EncryptorInterface $encryptor
     * @param EncodingHelper $helper
     * @param PlaceholderFactory $placeholderFactory
     * @param RequestFactory $requestFactory
     * @throws LocalizedException
     */
    public function __construct(
        private readonly EncryptorInterface $encryptor,
        private readonly EncodingHelper $helper,
        PlaceholderFactory $placeholderFactory,
        private readonly RequestFactory $requestFactory,
    ) {
        $this->placeholder = $placeholderFactory->create(PlaceholderFactory::TYPE_ENVIRONMENT);
    }

    /**
     * Execute the command
     *
     * @param array|null $environmentVariables
     * @return array
     * @throws \Exception
     */
    public function execute(?array $environmentVariables = null): array
    {
        if ($environmentVariables === null) {
            $environmentVariables = $this->requestFactory->create()->getEnv()->toArray();
        }

        $config = [];

        foreach ($environmentVariables as $template => $value) {
            if (!$this->placeholder->isApplicable($template)
                || !$this->helper->isEncryptedValue($value)
                || $this->helper->isAlreadyUpdated($value)) {
                continue;
            }

            $decryptedValue = $this->encryptor->decrypt($value);
            $newValue = $this->encryptor->encrypt($decryptedValue);
            $config[$template] = compact('value', 'newValue', 'decryptedValue');
        }

        return $config;
    }
}
