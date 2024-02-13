<?php


namespace App\Controller\Front;


use App\Repository\WineCardRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class StatisticsController extends AbstractController
{

    private function countByNames(array $data): array
    {
        $totalItems = array_reduce($data, function ($total, $item) {
            return $total += $item['cnt'];
        });
        $result = [];
        foreach ($data as $row) {
            $result[$row['name']] = round((100/$totalItems) * $row['cnt'], 0);
        }

        return $result;
    }

    /**
     * @Route("/cabinet/statistics/short", name="cabinet_statistics_short")
     */
    public function short(Request $request,
                          WineCardRepository $wineCardRepository,
                          SessionInterface $session): Response
    {
        $isAjax = $request->isXmlHttpRequest();

        $currentWineCard = $wineCardRepository->find($session->get('currentWineCard', 0));

        $user = $this->getUser();

        $wPrices = [];
        foreach ($this->countByNames($wineCardRepository->getWinePricesByUser($user)) as $value => $percent) {
            $value = intval($value);
            $end = 0 === $value ? 1000 : ($value + 1) * 1000;
            $start = $value * 1000;
            $name = "{$start} - {$end}";
            $wPrices[$name] = $percent;
        }

        // todo: сделать один запрос на все вина из карт, и одним циклом сгруппировать статистику

        return $this->render('front/statistics/short.html.twig', [
            'totalWines' => null !== $currentWineCard ? count($currentWineCard->getProducts()) : 0,
            'winePrices' => $wPrices,
            'wineColors' => $this->countByNames($wineCardRepository->getWineColorsByUser($user)),
            'wineSugars' => $this->countByNames($wineCardRepository->getWineSugarsByUser($user)),
            'countries' => $this->countByNames($wineCardRepository->getWineCountriesByUser($user)),
            'suppliers' => $this->countByNames($wineCardRepository->getSuppliersByUser($user)),
            'grapeSorts' => $this->countByNames($wineCardRepository->getGrapeSortsByUser($user)),
            'volumes' => $this->countByNames($wineCardRepository->getVolumesByUser($user)),
        ]);
    }

    private function transform()
    {
        
    }
    
}