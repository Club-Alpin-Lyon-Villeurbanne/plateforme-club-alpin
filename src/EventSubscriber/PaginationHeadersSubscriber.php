<?php

namespace App\EventSubscriber;

use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class PaginationHeadersSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', 0],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();
        
        // Vérifier si c'est une requête API Platform
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }
        
        // Récupérer les données depuis différents emplacements possibles
        $data = $request->attributes->get('data');
        if (!$data) {
            $data = $request->attributes->get('_api_data');
        }
        
        // Si c'est une collection API Platform
        $collection = $request->attributes->get('_api_collection_operation_name');
        $resourceClass = $request->attributes->get('_api_resource_class');
        
        if ($data instanceof PaginatorInterface) {
            $totalItems = $data->getTotalItems();
            $itemsPerPage = $data->getItemsPerPage();
            $currentPage = $data->getCurrentPage();
            $pageCount = ceil($totalItems / $itemsPerPage);
            
            $response->headers->set('X-Total-Count', (string) $totalItems);
            $response->headers->set('X-Page-Count', (string) $pageCount);
            $response->headers->set('X-Current-Page', (string) $currentPage);
            $response->headers->set('X-Items-Per-Page', (string) $itemsPerPage);
            
            // Ajouter des Link headers
            $this->addLinkHeaders($response, $currentPage, $pageCount, $request);
        } elseif ($data instanceof PartialPaginatorInterface) {
            // Pour la pagination partielle
            $itemsPerPage = $data->getItemsPerPage();
            $currentPage = $data->getCurrentPage();
            
            $response->headers->set('X-Current-Page', (string) $currentPage);
            $response->headers->set('X-Items-Per-Page', (string) $itemsPerPage);
        } elseif ($collection && is_array($data)) {
            // Si c'est un tableau simple (sans pagination), on peut au moins donner le count
            $response->headers->set('X-Total-Count', (string) count($data));
        }
    }
    
    private function addLinkHeaders($response, int $currentPage, int $pageCount, $request): void
    {
        $links = [];
        $baseUrl = $request->getSchemeAndHttpHost() . $request->getBaseUrl() . $request->getPathInfo();
        $queryParams = $request->query->all();
        
        // First
        $queryParams['page'] = 1;
        $links[] = sprintf('<%s?%s>; rel="first"', $baseUrl, http_build_query($queryParams));
        
        // Last
        $queryParams['page'] = $pageCount;
        $links[] = sprintf('<%s?%s>; rel="last"', $baseUrl, http_build_query($queryParams));
        
        // Previous
        if ($currentPage > 1) {
            $queryParams['page'] = $currentPage - 1;
            $links[] = sprintf('<%s?%s>; rel="prev"', $baseUrl, http_build_query($queryParams));
        }
        
        // Next
        if ($currentPage < $pageCount) {
            $queryParams['page'] = $currentPage + 1;
            $links[] = sprintf('<%s?%s>; rel="next"', $baseUrl, http_build_query($queryParams));
        }
        
        if (!empty($links)) {
            $response->headers->set('Link', implode(', ', $links));
        }
    }
}