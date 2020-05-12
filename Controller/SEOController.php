<?php
/**
 * NovaeZSEOBundle SEOController
 *
 * @package   Novactive\Bundle\eZSEOBundle
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use DOMDocument;

/**
 * Class SEOController
 */
class SEOController extends Controller
{
    /**
     * Robots.txt route
     *
     * @Route("/robots.txt")
     * @Method("GET")
     * @return Response
     */
    public function robotsAction()
    {
        $response = new Response();
        $response->setSharedMaxAge(86400);
        $robots = ['User-agent: *'];

        $robotsRules             = $this->getConfigResolver()->getParameter('robots', 'nova_ezseo');
        $backwardCompatibleRules = $this->getConfigResolver()->getParameter('robots_disallow', 'nova_ezseo');

        if (\is_array($robotsRules['sitemap'])) {
            foreach ($robotsRules['sitemap'] as $sitemapRules) {
                foreach ($sitemapRules as $key => $value) {
                    if ('route' === $key) {
                        $url      = $this->generateUrl($value, [], UrlGeneratorInterface::ABSOLUTE_URL);
                        $robots[] = "Sitemap: {$url}";
                    }
                    if ('url' === $key) {
                        $robots[] = "Sitemap: {$value}";
                    }
                }
            }
        }
        if (\is_array($robotsRules['allow'])) {
            foreach ($robotsRules['allow'] as $rule) {
                $robots[] = "Allow: {$rule}";
            }
        }
        if (\is_array($robotsRules['useragent'])) {
            foreach ($robotsRules['useragent'] as $rule) {
                $robots[] = "User-agent: {$rule["name"]}";
                if ($rule["disallow"] !== null)
                    $robots[] = "Disallow: {$rule["disallow"]}";
                if ($rule["allow"] !== null)
                    $robots[] = "Allow: {$rule["allow"]}";
            }
        }
        if ('prod' !== $this->getParameter('kernel.environment')) {
            $robots[] = 'Disallow: /';
        }

        if (\is_array($robotsRules['disallow'])) {
            foreach ($robotsRules['disallow'] as $rule) {
                $robots[] = "Disallow: {$rule}";
            }
        }

        if (\is_array($backwardCompatibleRules)) {
            foreach ($backwardCompatibleRules as $rule) {
                $robots[] = "Disallow: {$rule}";
            }
        }

        $response->setContent(implode("\n", $robots));
        $response->headers->set('Content-Type', 'text/plain');

        return $response;
    }

    /**
     * Google Verification route
     *
     * @param string $key
     * @Route("/google{key}.html", requirements={ "key": "[a-zA-Z0-9]*" })
     * @Method("GET")
     * @throws NotFoundHttpException
     * @return Response
     */
    public function googleVerifAction( $key )
    {
        if ( $this->getConfigResolver()->getParameter( 'google_verification', 'nova_ezseo' ) != $key )
        {
            throw new NotFoundHttpException( "Google Verification Key not found" );
        }
        $response = new Response();
        $response->setSharedMaxAge( 24 * 3600 );
        $response->setContent( "google-site-verification: google{$key}.html" );
        return $response;
    }

    /**
     * Bing Verification route
     *
     * @Route("/BingSiteAuth.xml")
     * @Method("GET")
     * @throws NotFoundHttpException
     * @return Response
     */
    public function bingVerifAction()
    {
        if ( !$this->getConfigResolver()->hasParameter( 'bing_verification', 'nova_ezseo' ) )
        {
            throw new NotFoundHttpException( "Bing Verification Key not found" );
        }

        $key = $this->getConfigResolver()->getParameter( 'bing_verification', 'nova_ezseo' );

        $xml = new DOMDocument("1.0", "UTF-8");
        $xml->formatOutput = true;

        $root = $xml->createElement("users");
        $root->appendChild($xml->createElement("user", $key));
        $xml->appendChild($root);

        $response = new Response($xml->saveXML());
        $response->setSharedMaxAge( 24 * 3600 );
        $response->headers->set("Content-Type", "text/xml");
        return $response;
    }
}
