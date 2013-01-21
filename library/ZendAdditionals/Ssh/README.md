# ZendAdditionals - SSH

This is a wrapper class for the ssh2 pecl extensions. 
Connection will be made when authenticate method will be called.

__Always authenticate by the server before doing any other actions.__

## Authentication

```php
  $ssh = new \ZendAdditionals\Ssh\Ssh('myhost');
  
  // Authenticate via credentials
  try {
    $ssh->authenticateByCredentials('username', 'password');
  } Catch (Exception $e) {
    // Handle any exceptions
  }

  // Authenticate via keys
  try {
    $public  = __DIR__.'/data/id_rsa.pub';
    $private = __DIR__.'/data/id_rsa';
  
    $ssh->authenticateByKey('username', $public, $private, 'mypassPhrase');
  } catch (Exception $e) {
    // Handle any exceptions
  }
```

## Executing command
```php
  try {
    // When you don't specify errorStream variable all the errors will be passed to the mainStream
    $ssh->exec('ls -l', $ioStream, true, $errorStream);

    $result = stream_get_contents($ioStream);
    $errors = stream_get_contents($errorStream);

    if (empty($errors) === false) {
      echo "Got an error when executing commando 'ls -l'. The error: {$errors}";
    } else {
      echo "The result: {$result}";
    }  
  } catch (Exception $e) {
    // Handle errors
  }
```

## Todo's
Still some work to do. Basic functionality has been covered. The follow functions should still be implemented

- Factory for SFTP functions
- Factory for SCP functions
- Method for creating a SSH tunnel
- Authentication method none. This can return the available authentication methods.

## More information

- http://www.php.net/manual/en/book.ssh2.php