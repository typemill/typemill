<?php
namespace Slim\Flash;

use ArrayAccess;
use RuntimeException;
use InvalidArgumentException;

/**
 * Flash messages
 */
class Messages
{
    /**
     * Messages from previous request
     *
     * @var string[]
     */
    protected $fromPrevious = [];

    /**
     * Messages for current request
     *
     * @var string[]
     */
    protected $forNow = [];

    /**
     * Messages for next request
     *
     * @var string[]
     */
    protected $forNext = [];

    /**
     * Message storage
     *
     * @var null|array|ArrayAccess
     */
    protected $storage;

    /**
     * Message storage key
     *
     * @var string
     */
    protected $storageKey = 'slimFlash';

    /**
     * Create new Flash messages service provider
     *
     * @param null|array|ArrayAccess $storage
     * @throws RuntimeException if the session cannot be found
     * @throws InvalidArgumentException if the store is not array-like
     */
    public function __construct(&$storage = null, $storageKey = null)
    {
        if (is_string($storageKey) && $storageKey) {
            $this->storageKey = $storageKey;
        }

        // Set storage
        if (is_array($storage) || $storage instanceof ArrayAccess) {
            $this->storage = &$storage;
        } elseif (is_null($storage)) {
            if (!isset($_SESSION)) {
                throw new RuntimeException('Flash messages middleware failed. Session not found.');
            }
            $this->storage = &$_SESSION;
        } else {
            throw new InvalidArgumentException('Flash messages storage must be an array or implement \ArrayAccess');
        }

        // Load messages from previous request
        if (isset($this->storage[$this->storageKey]) && is_array($this->storage[$this->storageKey])) {
            $this->fromPrevious = $this->storage[$this->storageKey];
        }
        $this->storage[$this->storageKey] = [];
    }

    /**
     * Add flash message for the next request
     *
     * @param string $key The key to store the message under
     * @param mixed  $message Message to show on next request
     */
    public function addMessage($key, $message)
    {
        // Create Array for this key
        if (!isset($this->storage[$this->storageKey][$key])) {
            $this->storage[$this->storageKey][$key] = [];
        }

        // Push onto the array
        $this->storage[$this->storageKey][$key][] = $message;
    }

    /**
     * Add flash message for current request
     *
     * @param string $key The key to store the message under
     * @param mixed  $message Message to show on next request
     */
    public function addMessageNow($key, $message)
    {
        // Create Array for this key
        if (!isset($this->forNow[$key])) {
            $this->forNow[$key] = [];
        }

        // Push onto the array
        $this->forNow[$key][] = $message;
    }

    /**
     * Get flash messages
     *
     * @return array Messages to show for current request
     */
    public function getMessages()
    {
        $messages = $this->fromPrevious;

        foreach ($this->forNow as $key => $values) {
            if (!isset($messages[$key])) {
                $messages[$key] = [];
            }

            foreach ($values as $value) {
                array_push($messages[$key], $value);
            }
        }

        return $messages;
    }

    /**
     * Get Flash Message
     *
     * @param string $key The key to get the message from
     * @return mixed|null Returns the message
     */
    public function getMessage($key)
    {
        $messages = $this->getMessages();

        // If the key exists then return all messages or null
        return (isset($messages[$key])) ? $messages[$key] : null;
    }

    /**
     * Get the first Flash message
     *
     * @param  string $key The key to get the message from
     * @param  string $default Default value if key doesn't exist
     * @return mixed Returns the message
     */
    public function getFirstMessage($key, $default = null)
    {
        $messages = self::getMessage($key);
        if (is_array($messages) && count($messages) > 0) {
            return $messages[0];
        }

        return $default;
    }

    /**
     * Has Flash Message
     *
     * @param string $key The key to get the message from
     * @return bool Whether the message is set or not
     */
    public function hasMessage($key)
    {
        $messages = $this->getMessages();
        return isset($messages[$key]);
    }

    /**
     * Clear all messages
     *
     * @return void
     */
    public function clearMessages()
    {
        if (isset($this->storage[$this->storageKey])) {
            $this->storage[$this->storageKey] = [];
        }

        $this->fromPrevious = [];
        $this->forNow = [];
    }

    /**
     * Clear specific message
     *
     * @param  String $key The key to clear
     * @return void
     */
    public function clearMessage($key)
    {
        if (isset($this->storage[$this->storageKey][$key])) {
            unset($this->storage[$this->storageKey][$key]);
        }

        if (isset($this->fromPrevious[$key])) {
            unset($this->fromPrevious[$key]);
        }

        if (isset($this->forNow[$key])) {
            unset($this->forNow[$key]);
        }
    }
}
