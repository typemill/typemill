<?php
namespace Slim\Flash\Tests;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Flash\Messages;

class MessagesTest extends \PHPUnit_Framework_TestCase
{
    // Test get messages from previous request
    public function testGetMessagesFromPrevRequest()
    {
        $storage = ['slimFlash' => ['Test']];
        $flash = new Messages($storage);

        $this->assertEquals(['Test'], $flash->getMessages());
    }

    // Test a string can be added to a message array for the current request
    public function testAddMessageFromAnIntegerForCurrentRequest()
    {
        $storage = ['slimFlash' => []];
        $flash   = new Messages($storage);

        $flash->addMessageNow('key', 46);
        $flash->addMessageNow('key', 48);

        $messages = $flash->getMessages();
        $this->assertEquals(['46','48'], $messages['key']);

        $this->assertArrayHasKey('slimFlash', $storage);
        $this->assertEmpty($storage['slimFlash']);
    }

    // Test a string can be added to a message array for the current request
    public function testAddMessageFromStringForCurrentRequest()
    {
        $storage = ['slimFlash' => []];
        $flash   = new Messages($storage);

        $flash->addMessageNow('key', 'value');

        $messages = $flash->getMessages();
        $this->assertEquals(['value'], $messages['key']);

        $this->assertArrayHasKey('slimFlash', $storage);
        $this->assertEmpty($storage['slimFlash']);
    }

    // Test an array can be added to a message array for the current request
    public function testAddMessageFromArrayForCurrentRequest()
    {
        $storage = ['slimFlash' => []];
        $flash   = new Messages($storage);

        $formData = [
            'username'     => 'Scooby Doo',
            'emailAddress' => 'scooby@mysteryinc.org',
        ];

        $flash->addMessageNow('old', $formData);

        $messages = $flash->getMessages();
        $this->assertEquals($formData, $messages['old'][0]);

        $this->assertArrayHasKey('slimFlash', $storage);
        $this->assertEmpty($storage['slimFlash']);
    }

    // Test an object can be added to a message array for the current request
    public function testAddMessageFromObjectForCurrentRequest()
    {
        $storage = ['slimFlash' => []];
        $flash   = new Messages($storage);

        $user = new \stdClass();
        $user->name         = 'Scooby Doo';
        $user->emailAddress = 'scooby@mysteryinc.org';

        $flash->addMessageNow('user', $user);

        $messages = $flash->getMessages();
        $this->assertInstanceOf(\stdClass::class, $messages['user'][0]);

        $this->assertArrayHasKey('slimFlash', $storage);
        $this->assertEmpty($storage['slimFlash']);
    }

    // Test a string can be added to a message array for the next request
    public function testAddMessageFromAnIntegerForNextRequest()
    {
        $storage = ['slimFlash' => []];
        $flash   = new Messages($storage);

        $flash->addMessage('key', 46);
        $flash->addMessage('key', 48);

        $this->assertArrayHasKey('slimFlash', $storage);
        $this->assertEquals(['46', '48'], $storage['slimFlash']['key']);
    }

    // Test a string can be added to a message array for the next request
    public function testAddMessageFromStringForNextRequest()
    {
        $storage = ['slimFlash' => []];
        $flash   = new Messages($storage);

        $flash->addMessage('key', 'value');

        $this->assertArrayHasKey('slimFlash', $storage);
        $this->assertEquals(['value'], $storage['slimFlash']['key']);
    }

    // Test an array can be added to a message array for the next request
    public function testAddMessageFromArrayForNextRequest()
    {
        $storage = ['slimFlash' => []];
        $flash   = new Messages($storage);

        $formData = [
            'username'     => 'Scooby Doo',
            'emailAddress' => 'scooby@mysteryinc.org',
        ];

        $flash->addMessage('old', $formData);

        $this->assertArrayHasKey('slimFlash', $storage);
        $this->assertEquals($formData, $storage['slimFlash']['old'][0]);
    }

    // Test an object can be added to a message array for the next request
    public function testAddMessageFromObjectForNextRequest()
    {
        $storage = ['slimFlash' => []];
        $flash   = new Messages($storage);

        $user = new \stdClass();
        $user->name         = 'Scooby Doo';
        $user->emailAddress = 'scooby@mysteryinc.org';

        $flash->addMessage('user', $user);

        $this->assertArrayHasKey('slimFlash', $storage);
        $this->assertInstanceOf(\stdClass::class, $storage['slimFlash']['user'][0]);
    }

    // Test get empty messages from previous request
    public function testGetEmptyMessagesFromPrevRequest()
    {
        $storage = [];
        $flash = new Messages($storage);

        $this->assertEquals([], $flash->getMessages());
    }

    // Test set messages for current request
    public function testSetMessagesForCurrentRequest()
    {
        $storage = ['slimFlash' => [ 'error' => ['An error']]];

        $flash = new Messages($storage);
        $flash->addMessageNow('error', 'Another error');
        $flash->addMessageNow('success', 'A success');
        $flash->addMessageNow('info', 'An info');

        $messages = $flash->getMessages();
        $this->assertEquals(['An error', 'Another error'], $messages['error']);
        $this->assertEquals(['A success'], $messages['success']);
        $this->assertEquals(['An info'], $messages['info']);

        $this->assertArrayHasKey('slimFlash', $storage);
        $this->assertEmpty([], $storage['slimFlash']);
    }

    // Test set messages for next request
    public function testSetMessagesForNextRequest()
    {
        $storage = [];
        
        $flash = new Messages($storage);
        $flash->addMessage('Test', 'Test');
        $flash->addMessage('Test', 'Test2');

        $this->assertArrayHasKey('slimFlash', $storage);
        $this->assertEquals(['Test', 'Test2'], $storage['slimFlash']['Test']);
    }
    
    //Test getting the message from the key
    public function testGetMessageFromKey()
    {
        $storage = ['slimFlash' => [ 'Test' => ['Test', 'Test2']]];
        $flash = new Messages($storage);

        $this->assertEquals(['Test', 'Test2'], $flash->getMessage('Test'));
    }

    //Test getting the first message from the key
    public function testGetFirstMessageFromKey()
    {
        $storage = ['slimFlash' => [ 'Test' => ['Test', 'Test2']]];
        $flash = new Messages($storage);

        $this->assertEquals('Test', $flash->getFirstMessage('Test'));
    }

    //Test getting the default message if the key doesn't exist
    public function testDefaultFromGetFirstMessageFromKeyIfKeyDoesntExist()
    {
        $storage = ['slimFlash' => []];
        $flash = new Messages($storage);

        $this->assertEquals('This', $flash->getFirstMessage('Test', 'This'));
    }

    //Test getting the message from the key
    public function testGetMessageFromKeyIncludingCurrent()
    {
        $storage = ['slimFlash' => [ 'Test' => ['Test', 'Test2']]];
        $flash = new Messages($storage);
        $flash->addMessageNow('Test', 'Test3');

        $messages = $flash->getMessages();

        $this->assertEquals(['Test', 'Test2','Test3'], $flash->getMessage('Test'));
    }

    public function testHasMessage()
    {
        $storage = ['slimFlash' => []];
        $flash = new Messages($storage);
        $this->assertFalse($flash->hasMessage('Test'));

        $storage = ['slimFlash' => [ 'Test' => ['Test']]];
        $flash = new Messages($storage);
        $this->assertTrue($flash->hasMessage('Test'));
    }

    public function testClearMessages()
    {
        $storage = ['slimFlash' => []];
        $flash = new Messages($storage);

        $storage = ['slimFlash' => [ 'Test' => ['Test']]];
        $flash = new Messages($storage);
        $flash->addMessageNow('Now', 'hear this');
        $this->assertTrue($flash->hasMessage('Test'));
        $this->assertTrue($flash->hasMessage('Now'));

        $flash->clearMessages();
        $this->assertFalse($flash->hasMessage('Test'));
        $this->assertFalse($flash->hasMessage('Now'));
    }

    public function testClearMessage()
    {
        $storage = ['slimFlash' => []];
        $flash = new Messages($storage);

        $storage = ['slimFlash' => [ 'Test' => ['Test'], 'Foo' => ['Bar']]];
        $flash = new Messages($storage);
        $flash->addMessageNow('Now', 'hear this');
        $this->assertTrue($flash->hasMessage('Test'));
        $this->assertTrue($flash->hasMessage('Foo'));
        $this->assertTrue($flash->hasMessage('Now'));

        $flash->clearMessage('Test');
        $flash->clearMessage('Now');
        $this->assertFalse($flash->hasMessage('Test'));
        $this->assertFalse($flash->hasMessage('Now'));
        $this->assertTrue($flash->hasMessage('Foo'));
    }

    public function testSettingCustomStorageKey()
    {
        $storage = ['some-key' => [ 'Test' => ['Test']]];
        $flash = new Messages($storage);
        $this->assertFalse($flash->hasMessage('Test'));

        $flash = new Messages($storage, 'some-key');
        $this->assertTrue($flash->hasMessage('Test'));
    }
}
