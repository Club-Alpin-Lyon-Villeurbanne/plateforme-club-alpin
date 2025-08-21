<?php

namespace App\EventSubscriber;

use ApiPlatform\State\Pagination\PaginatorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class PaginationHeadersSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();
        
        // Vérifier si c'est une requête API Platform
        if (!$request->attributes->has('_api_resource_class')) {
            return;
        }
        
        $data = $request->attributes->get('data');
        
        if ($data instanceof PaginatorInterface) {
            $response->headers->set('X-Total-Count', $data->getTotalItems());
            $response->headers->set('X-Page-Count', ceil($data->getTotalItems() / $data->getItemsPerPage()));
            $response->headers->set('X-Current-Page', $data->getCurrentPage());
            $response->headers->set('X-Items-Per-Page', $data->getItemsPerPage());
            
            // Optionnel : ajouter des Link headers
            $this->addLinkHeaders($response, $data, $request);
        }
    }
    
    private function addLinkHeaders($response, $data, $request): void
    {
        $links = [];
        $baseUrl = $request->getSchemeAndHttpHost() . $request->getBaseUrl() . $request->getPathInfo();
        $queryParams = $request->query->all();
        
        // First
        $queryParams['page'] = 1;
        $links[] = sprintf('<%s?%s>; rel="first"', $baseUrl, http_build_query($queryParams));
        
        // Last
        $lastPage = ceil($data->getTotalItems() / $data->getItemsPerPage());
        $queryParams['page'] = $lastPage;
        $links[] = sprintf('<%s?%s>; rel="last"', $baseUrl, http_build_query($queryParams));
        
        // Previous
        if ($data->getCurrentPage() > 1) {
            $queryParams['page'] = $data->getCurrentPage() - 1;
            $links[] = sprintf('<%s?%s>; rel="prev"', $baseUrl, http_build_query($queryParams));
        }
        
        // Next
        if ($data->getCurrentPage() < $lastPage) {
            $queryParams['page'] = $data->getCurrentPage() + 1;
            $links[] = sprintf('<%s?%s>; rel="next"', $baseUrl, http_build_query($queryParams));
        }
        
        $response->headers->set('Link', implode(', ', $links));
    }
}