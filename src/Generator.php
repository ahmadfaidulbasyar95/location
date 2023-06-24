<?php

namespace Emsifa\ApiWilayah;

class Generator
{
    /**
     * @var Repository
     */
    protected $repository;

    /**
     * @var string
     */
    protected $outputDir;

    public function __construct(Repository $repository, string $outputDir)
    {
        $this->repository = $repository;
        $this->outputDir = Helper::resolvePath($outputDir);
    }

    public function clearOutputDir()
    {
        $files = glob($this->outputDir.'/*');
        foreach ($files as $file) {
            Helper::removeFileOrDirectory($file);
        }

        return $this;
    }

    public function generate()
    {
        set_time_limit(3600);
        $provinces = $this->repository->getProvinces();
        foreach ($provinces as $k => $v) {
            $provinces[$k] = [$v['id'],$v['name']];
        }
        $this->generateApi("/provinces.json", $provinces);

        foreach ($provinces as $province) {
            $regencies = $this->repository->getRegenciesByProvinceId($province[0]);
            foreach ($regencies as $k => $v) {
                $regencies[$k] = [$v['id'],$v['name']];
            }
            $this->generateApi("/regencies/{$province[0]}.json", $regencies);

            foreach ($regencies as $regency) {
                $districts = $this->repository->getDistrictsByRegencyId($regency[0]);
                foreach ($districts as $k => $v) {
                    $districts[$k] = [$v['id'],$v['name']];
                }
                $this->generateApi("/districts/{$regency[0]}.json", $districts);

                foreach ($districts as $district) {
                    $villages = $this->repository->getVillagesByDistrictId($district[0]);
                    foreach ($villages as $k => $v) {
                        $villages[$k] = [$v['id'],$v['name']];
                    }
                    $this->generateApi("/villages/{$district[0]}.json", $villages);

                }
            }
        }
        echo 'Selesai !!!!!!!!!!!!!';
    }

    public function generateApi(string $uri, array $data)
    {
        $path = Helper::resolvePath($uri);

        $this->makeDirectoriesIfNotExists(dirname($path));

        $filePath = $this->getPath($path);
        file_put_contents($filePath, json_encode($data));
    }

    public function makeDirectoriesIfNotExists(string $path)
    {
        $path = ltrim($path, DIRECTORY_SEPARATOR);
        $dirs = explode(DIRECTORY_SEPARATOR, $path);

        $path = "";
        foreach ($dirs as $dir) {
            $path .= DIRECTORY_SEPARATOR . $dir;
            $dirPath = $this->getPath($path);
            if (!is_dir($dirPath)) {
                mkdir($dirPath);
            }
        }
    }

    public function getPath(string $path)
    {
        $ds = DIRECTORY_SEPARATOR;
        return rtrim($this->outputDir, $ds) . $ds . trim($path, $ds);
    }

}
