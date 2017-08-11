<?php

namespace VmailBundle\Utils;

use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;

/*
    app.readcsv:
        class: OCAX\Common\Utils\ReadCSV
        arguments: ['@service_container']

*/
class ReadCSV extends ContainerAwareLoader
{
    private $rootdir; //%kernel_root_dir% on startup via services.yml
    private $basedir='../app/Resources/defaults/';
    private $container; // @service_container on startup

    public function __construct($container)
    {
        $this->container = $container;
        $this->basedir='../app/Resources/defaults/';
    }

    public function setRootdir($rootdir)
    {
        $this->rootdir = $rootdir;
    }

    public function getBaseDir()
    {
        if (empty($this->basedir)) {
            $this->setBasedir($this->container->getParameter('csv_rootdir'));
        }

        return $this->basedir;
    }

    public function setBasedir($basedir)
    {
        $this->basedir = $basedir;
    }

    public function getFullbase()
    {
        return $this->rootdir . '/' . $this->getBaseDir();
    }

    public function readcsv($csvfile)
    {
        // print getcwd();
        print $this->getFullbase()."\n$csvfile\n";

        if (($handle = fopen($this->getFullbase()."$csvfile", "r")) !== false) {
            $headers = array();
            $data = array();
            $row = 0;
            while (($line = fgetcsv($handle, 1000, ",")) !== false) {
                $row++;
                $column = 0;
                if ($row === 1) {
                    $num = count($line);
                    echo "<p> $num fields in line $row: <br /></p>\n";
                    foreach ($line as $key) {
                        $headers[$column] = $key;
                        $column++;
                    }
                } else {
                    foreach ($line as $value) {
                        $data[$row]["{$headers[$column]}"] = $value;
                        $column++;
                    }
                }
            }
        } else {
            die(print_r($data));
        }
        return $data;
    }

    public function emdumprow($em, $params)
    {
        $row = $params['row'];
        $fields = $params['fields'];
        $classname = $params['classname'];
        $namespace = $params['namespace'];
        eval('$class = new ' . $namespace . '\Entity\\' . $classname .'();');
        foreach ($row as $field => $value) {
            if (!empty($fields["$field"])) {
                printf("<strong>field</strong>: (line %s) (%s) ", __LINE__, $field);
                echo "<strong>value</strong>: ($value)\n<br>";
                $property = $field;
                if (!is_array($fields["$field"])) {
                    $function = '$class->set' . ucfirst($property) . '(\'' . $value. '\');';
                } else {
                    $properties = $fields["$field"];
                    printf("<strong>properties</strong>: (line %s), (%s)\n<br>", __LINE__, print_r($properties, true));
                    if (!empty($properties['type'])) {
                        $types=$properties['type'];
                        foreach ($types as $type => $v) {
                            printf("<strong>type</strong>: (line %s), (%s)<br>", __LINE__, print_r($type, true));
                            switch ($type) {
                                case 'date':
                                    $date = $types['date'];
                                    printf("<strong>date</strong>: (line %s) (%s)<br>", __LINE__, print_r($date, true));
                                    $format = $types['format'];
                                    $classobject = \DateTime::createFromFormat($format, $value);
                                    printf("<strong>classobject</strong>: (line %s) (%s)<br>", __LINE__, $classobject->format($format));
                                    break;
                                case 'entity':
                                    $entity = $properties['type'];
                                    printf("<strong>entity</strong>: (line %s) (%s)<br>", __LINE__, print_r($entity, true));
                                    $property = $entity['property'];
                                    $classname = $entity['classname'];
                                    $mappedby = $entity['mappedBy'];
                                    $namespace = $entity['namespace'];
                                    printf("<strong>classname</strong>: (line %s) (%s)<br>", __LINE__, $classname);
                                    $eval = '$classobject = new ' . $namespace .'\Entity\\'. $classname . '();';
                                    printf("eval (line %s): (%s)", __LINE__, $eval);
                                    eval($eval);
                                    $classobject = $em->getRepository('VmailBundle:' . $classname)
                                        ->findOneBy(array($mappedby => $value));
                                    break;
                                default:
                                    # code...
                                    break;
                            }
                        }
                        $function= '$class->set' . ucfirst($property) . '($classobject);';
                    } else {
                        //$function= '$security->set' . ucfirst($property) . '($object);';
                    }
                }
                printf("\n<strong>function</strong> (line %s): %s\n<br><br>", __LINE__, $function);
                eval($function);
            }
        }
        return $class;
    }
}
