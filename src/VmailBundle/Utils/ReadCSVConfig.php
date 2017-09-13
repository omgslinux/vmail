<?php

namespace VmailBundle\Utils;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use VmailBundle\Entity\Config;

/**
 * Securities controller.
 *
 * @Route("/utils/readcsv/config")
 */
class ReadCSVConfig extends Controller
{

    /**
     * Loads the contents of a csv file into Securities from a FundBanks entity
     *
     * @Route("/load", name="readcsv_config_load")
     * @Method({"GET", "POST"})
     */
    public function loadAction(Request $request, Config $config)
    {
        $filename = urldecode($request->get('filename'));
        $records = $this->get('app.readcsv')->readcsv($filename);
        $fields = array(
            'parameter' => true,
            'value' => true,
            'description' => true,
            'required' => true,
            'editable' => true,
            'startdate' => array(
                'type' => array(
                    'date' => true,
                    'format' => 'Y-m-d'
                )
            ),
            'duration' => true,
            'amount' => true,
            'city' => array(
                'type' => array(
                    'entity' => true,
                    'classname' => 'Cities',
                    'namespace' => 'Shared',
                    'property' => 'city',
                    'mappedBy' => 'city'
                )
            ),
            'city_id' => array(
                'type' => array(
                    'entity' => true,
                    'classname' => 'Cities',
                    'namespace' => 'Shared',
                    'property' => 'city',
                    'mappedBy' => 'id'
                )
            ),
        );
        printf("fields: (%s)\n<br><br>", print_r($fields, true));

        $em = $this->getDoctrine()->getManager();
        foreach ($records as $recordkey => $record) {
            printf("recordkey: (%s)\n<br><br>", print_r($record, true));
            //$security = new Securities();
            $params=array(
                'fields' => $fields,
                'classname'  => 'Config',
                'namespace' => 'OCAX\Common',
                'row' => $record,
            );
            $config = $this->get('app.readcsv')->emdumprow($em, $params);
            $em->persist($config);
        }
        $em->flush();

        return $this->redirectToRoute('user_panel');
    }
}
