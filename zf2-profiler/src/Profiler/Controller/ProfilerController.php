<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Profiler\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

class ProfilerController extends AbstractActionController
{
    public function indexAction()
    {
        $sm         = $this->getServiceLocator();
        $aGetParams = $this->params()->fromQuery();
        $options    = $sm->get('Profiler\Config');

		$aProfilerOptions = $this->setProfilerServiceOptions();
        $oPaginator = $sm->get('Profiler\ProfilerService')->getProfilesPaginator($aProfilerOptions['pagination']['page'], $aProfilerOptions['pagination']['count']);

        $this->layout('profiler/layout');
        $this->layout()->setVariable('portalHomeRouteParams', $options->getPortalHomeRouteParams());
        $viewModel = new ViewModel();
        $viewModel->setTemplate('profiler/index');
        $viewModel->setVariables(array(
            'graphs'     => $options->getGraphNames(),
            'page'       => $aProfilerOptions['pagination']['page'],
            'count'      => $aProfilerOptions['pagination']['count'],
            'urlOptions' => array('query' => $aGetParams),
            'paginator'  => $oPaginator,
        ));
        return $viewModel;
    }

    public function dataAction()
    {
        $sm    = $this->getServiceLocator();
        $iPage = $this->params('page');
        $aData = $sm->get('Profiler\ProfilerService')->fetchAllProfiles();
        return new JsonModel($aData);
    }

    public function graphDataAction()
    {
        $sm         = $this->getServiceLocator();
        $aGetParams = $this->params()->fromQuery();
        $sOrder     = isset($aGetParams['order']) ? $aGetParams['order'] : 'log';
        $oProfilerService = $sm->get('Profiler\ProfilerService');

		$aProfilerOptions = $this->setProfilerServiceOptions();

        switch ($sOrder) {
            case 'db':
                $aData = $oProfilerService->fetchAllProfilesByDbTime();
                break;
            case 'time':
                $aData = $oProfilerService->fetchAllProfilesByExecutionTime();
                break;
            case 'memory':
                $aData = $oProfilerService->fetchAllProfilesByMemoryUsage();
                break;
            default:
                $aData = $oProfilerService->fetchAllProfilesByLogTime();
                break;
        }
        $aGraphData = $this->aggregateProfilerDataPerCollectorTypes($aData);
		$aGraphData['count'] = $aData->count();

        return new JsonModel($aGraphData);
    }

    public function staticAction()
    {
        $viewModel = new ViewModel();
        $viewModel->setTemplate('profiler/static');
        $viewModel->setTerminal(true);
        $viewModel->setVariables(array('resource' => $this->params('resource')));
        return $viewModel;
    }

    protected function aggregateProfilerDataPerCollectorTypes($aData)
    {
        $aAggregatedData = array();
        foreach ($aData as $aProfileData) {
            foreach ($aProfileData as $sCollector => $aCollectedData) {
                if ($sCollector === '_id') {
                    $id = $aCollectedData->__toString();
                } else {
                    /* $aCollectedData['run'] = $id; */
                    $aAggregatedData[$sCollector][] = $aCollectedData;
                }
            }
        }
        return $aAggregatedData;
    }

	protected function setProfilerServiceOptions()
	{
        $sm         = $this->getServiceLocator();
        $aGetParams = $this->params()->fromQuery();
        $options    = $sm->get('Profiler\Config');

        $aPaginationOptions = array(
            'page' => $this->params('page', 1),
            'count' => isset($aGetParams['count']) ? $aGetParams['count'] : $options->getProfilesCountPerPage(),
        );
        $sm->get('Profiler\ProfilerService')->setPaginationSettings($aPaginationOptions);

		$aFilteringOptions = array(
			'text' => isset($aGetParams['filter']) ? $aGetParams['filter'] : false,
		);
		$sm->get('Profiler\ProfilerService')->setFilteringOptions($aFilteringOptions);

		return array(
			'pagination' => $aPaginationOptions,
			'filter'     => $aFilteringOptions,
		);
	}
}
