<?php
class ProxyHandler
{
    private $url;
    private $translated_url;
    private $curl_handler;

    function __construct($url, $proxy_url)
    {
        $this->url = $url;
        $this->proxy_url = $proxy_url;

        // Parse all the parameters for the URL
        if (isset($_SERVER['PATH_INFO']))
        {
            $proxy_url .= $_SERVER['PATH_INFO'];
        }
        else
        {
            $proxy_url .= '/';
        }

        if ($_SERVER['QUERY_STRING'] !== '')
        {
            $proxy_url .= "?{$_SERVER['QUERY_STRING']}";
        }

        $this->translated_url = $proxy_url;

        $this->curl_handler = curl_init($proxy_url);

        // Set various options
        $this->setCurlOption(CURLOPT_RETURNTRANSFER, true);
        $this->setCurlOption(CURLOPT_BINARYTRANSFER, true); // For images, etc.
        $this->setCurlOption(CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        $this->setCurlOption(CURLOPT_WRITEFUNCTION, array(
            $this,
            'readResponse'
        ));
        $this->setCurlOption(CURLOPT_HEADERFUNCTION, array(
            $this,
            'readHeaders'
        ));

        // Process post data.
        if (count($_POST))
        {
            // Empty the post data
            $post = array();

            // Set the post data
            $this->setCurlOption(CURLOPT_POST, true);

            // Encode and form the post data
            foreach ($_POST as $key => $value)
            {
                $post[] = urlencode($key) . "=" . urlencode($value);
            }

            $this->setCurlOption(CURLOPT_POSTFIELDS, implode('&', $post));

            unset($post);
        }
        elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') // Default request method is 'get'
        
        {
            // Set the request method
            $this->setCurlOption(CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);
        }

    }

    // Executes the proxy.
    public function execute()
    {
        curl_exec($this->curl_handler);
    }

    // Get the information about the request.
    // Should not be called before exec.
    public function getCurlInfo()
    {
        return curl_getinfo($this->curl_handler);
    }

    // Sets a curl option.
    public function setCurlOption($option, $value)
    {
        curl_setopt($this->curl_handler, $option, $value);
    }

    protected function readHeaders(&$cu, $string)
    {
        $length = strlen($string);
        if (preg_match(',^Location:,', $string))
        {
            $string = str_replace($this->proxy_url, $this->url, $string);
        }
        header($string);
        return $length;
    }

    protected function readResponse(&$cu, $string)
    {
        $length = strlen($string);
        echo $string;
        return $length;
    }

/*
the >>>>> .htaccess file:

RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f [NC]
RewriteCond %{REQUEST_FILENAME} !-d [NC]
RewriteCond %{REQUEST_URI} !^/index.php
RewriteRule ^(.+)$ index.php/$1 [QSA]
*/
}
//USAGE
$host = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_ENV['FORCE_HTTPS']) && $_ENV['FORCE_HTTPS'] == 'true')) ? 'https' : 'http';
$host .= '://' . $_SERVER['HTTP_HOST']."/";
//Available Servers
$servers = array(
				'http://localhost:9091/',
				'http://localhost:9092/',
				'http://localhost:9093/',
				'http://localhost:9094/'
				);
$proxyTo = $servers[array_rand($servers)];
//exit("Proxxied to: $proxyTo");
//Proceed
$proxy = new ProxyHandler($host,$proxyTo);
$proxy->execute();
?>
