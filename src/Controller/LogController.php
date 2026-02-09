<?php

declare(strict_types=1);

namespace Dragonwize\DwLogBundle\Controller;

use Dragonwize\DwLogBundle\Repository\LogRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

#[Route('/dw-logs', name: 'dw_log_')]
final class LogController
{
    public function __construct(
        private readonly LogRepository $logRepository,
        private readonly Environment $twig
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page    = max(1, $request->query->getInt('page', 1));
        $search  = $request->query->getString('search', '');
        $level   = $request->query->getString('level', '');
        $channel = $request->query->getString('channel', '');
        $limit   = 50;

        $result = $this->logRepository->findWithPagination(
            page: $page,
            limit: $limit,
            search: $search !== '' ? $search : null,
            level: $level !== '' ? $level : null,
            channel: $channel !== '' ? $channel : null
        );

        $totalItems = $result['total'];
        $totalPages = (int) ceil($totalItems / $limit);

        return new Response($this->twig->render('@DwLog/log/index.html.twig', [
            'logs'         => $result['logs'],
            'currentPage'  => $page,
            'totalPages'   => $totalPages,
            'totalItems'   => $totalItems,
            'search'       => $search,
            'level'        => $level,
            'channel'      => $channel,
            'levels'       => $this->logRepository->getDistinctLevels(),
            'channels'     => $this->logRepository->getDistinctChannels(),
        ]));
    }
}
