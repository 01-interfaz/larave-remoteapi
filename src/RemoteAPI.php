<?php


namespace Interfaz;

/**
 * Para laravel agregar en .env las API que se requiren.
 * APP_REMOTE_API_{NAME}="http://127.0.0.1:8000/api/v1/"
 * APP_REMOTE_API_{NAME}_KEY="123456"
 */
class RemoteAPI
{
    public string $apiTokenKeyName = "api_token";
    private string $endpoint;
    private string $endpointKey;
    private array $response_cookies = [];
    private array $request_cookies = [];

    private const REGEX_COOKIE = '/^Set-Cookie:\s*([^;]+)/';

    public function __construct($name)
    {
        $this->endpoint = env('APP_REMOTE_API_' . strtoupper($name));
        $this->endpointKey = env('APP_REMOTE_API_' . strtoupper($name) . '_KEY');
    }

    public function getURL($url) : string
    {
        return $this->endpoint . $url . '?' . $this->apiTokenKeyName . '=' . $this->endpointKey;
    }

    public function get(string $url, ?string $query = null) : string
    {
        return $this->execute('GET', $url, $query);
    }

    public function post(string $url, ?string $query = null) : string
    {
        return $this->execute('POST', $url, $query);
    }

    public function put(string $url, ?string $query = null) : string
    {
        return $this->execute('PUT', $url, $query);
    }

    public function delete(string $url, ?string $query = null) : string
    {
        return $this->execute('DELETE', $url, $query);
    }

    private function execute(string $method, string $url, ?string $query = null) : string
    {
        $context = $this->getContext($method);
        $url = $this->getURL($url);
        if($query !== null) $url .= "&". $query;
        $content = file_get_contents($url, false, $context);

        $cookies = array();
        foreach ($http_response_header as $hdr) {
            if (preg_match(self::REGEX_COOKIE, $hdr, $matches)) {
                parse_str($matches[1], $tmp);
                $cookies += $tmp;
            }
        }
        $this->response_cookies = $cookies;

        return $content;
    }

    public function getCookies(): array
    {
        return $this->response_cookies;
    }

    public function setCookies($name, $value) : void
    {
        $this->request_cookies[$name] = "$name=$value";
    }

    private function getCookiesString(): string
    {
        $output = "";
        foreach ($this->request_cookies as $key => $cookie) $output .= $cookie . ";";
        $output = rtrim($output, ';');
        return $output;
    }

    /**
     * @param string $method
     * @return resource
     */
    public function getContext(string $method)
    {
        $opts = array('http' => array('method' => $method, 'header' => "Cookie: " . $this->getCookiesString()));
        return stream_context_create($opts);
    }
}
