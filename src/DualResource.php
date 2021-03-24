<?php

namespace HamidMp\DualResource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class DualResource extends JsonResource
{

    /**
     * The "data" wrapper that should be applied. In response.
     *
     * @var string
     */
    public static $wrap = 'data';

    /**
     * The "data" wrapper that should be applied. In request.
     *
     * @var string
     */
    public static $wrap_request='data';

    protected $parameters;

    /**
     * The request instance
     *
     * @var
     */
    protected $request;

    private $wrap_dataArray=[];

    /**
     * Declaring assign-map between model and data
     *
     * @return array
     */
    protected function mapFields(){
        return [];
    }

    /**
     * Default values for request data.
     *
     * @return array
     */
    protected function defaultValues(){
        return [];
    }

    public function __construct($resource)
    {
        parent::__construct($resource);
        $this->parameters=[];
    }

    /**
     * Create a new resource instance from Model.
     *
     * @param mixed ...$parameters
     * @return static
     */
    public static function fromModel(...$parameters):self{
        return self::make(...$parameters);
    }

    /**
     * Create a new anonymous resource collection from Models.
     *
     * @param $resource
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public static function fromModels($resource){
        return self::collection($resource);
    }

    /**
     * Create a new DualResource instance from request data
     *
     * @param array $requestData
     * @return DualResource
     */
    public static function fromRequestData(array $requestData):DualResource{

        $dualResource=new static(null);
        $dualResource->wrap_dataArray=$requestData;

        return $dualResource;

    }

    /**
     * @param false $all
     * @return array
     */
    private function generateParametersFromRequest($all=false){

        //NOTE: all=true means all data from wrap_dataArray which specified fields has mapped
        //NOTE: all=false means only specified fields has mapped from wrap_dataArray

        $params=$this->generateParametersFromWrapData($all);

        return $params;
    }

    /**
     * Map founded request data by mapFields.
     *
     * @param false $all
     * @return array
     * @throws \Exception
     */
    private function generateParametersFromWrapData($all=false){

        //NOTE: all=true means all data from wrap_dataArray which specified fields has mapped
        //NOTE: all=false means only specified fields has mapped from wrap_dataArray

        $params=[];

        $wrap_dataArray=$this->wrap_dataArray;

        if(empty($wrap_dataArray)) {
            $this->parameters=[];
            return $params;
        }else if(is_array($wrap_dataArray) && !Arr::isAssoc($wrap_dataArray)){
            $result=[];
            foreach ($wrap_dataArray as $dataItem){
                $result[]=$this->reverseToArray($dataItem, $all);
            }
            $this->parameters = $result;
            $params=$result;
        }else{
            $params= $this->parameters = $this->reverseToArray($wrap_dataArray, $all);
        }

        return $params;
    }

    /**
     * Find data from request object depend on wrap_request
     */
    private function initWrapData(){
        $this->wrap_dataArray=[];

        if(empty($this->request)){
            return;
        }

        $dataArray=$this->request->all();

        if(empty($dataArray)) {
            return;
        }

        if (!empty(self::$wrap_request)) {

            if (!$this->request->filled(self::$wrap_request)) {
                //not wrap founded
                return ;
            }

            $this->wrap_dataArray = Arr::get($dataArray, self::$wrap_request);

        }else{
            $this->wrap_dataArray = $dataArray;
        }

    }

    /**
     * Create a new DualResource instance from request object
     *
     * @param Request $request
     * @return DualResource
     */
    public static function fromRequest(Request $request):DualResource{
        $dualResource=new static(null);
        $dualResource->request=$request;
        $dualResource->initWrapData();

        return $dualResource;

    }

    private function hasDefaultValue($viewFiled){
        $defValues=$this->defaultValues();
        if(empty($defValues))
            return null;
        if(isset($defValues[$viewFiled]))
            return $defValues[$viewFiled];
        return null;
    }

    /**
     * Map data (maybe from request) depend on MapFields
     *
     * @param $viewData
     * @param false $all
     * @return array
     * @throws \Exception
     */
    private function reverseToArray($viewData, $all=false){

        $mapfields=$this->mapFields();

        if(empty($mapfields))
            return $viewData;

        $modelData=[];
        foreach ($viewData as $vf=>$vv){

            $mf=Arr::get($mapfields,$vf);
            if(empty($mf)){
                if($all) {
                    $modelData[$vf] = $vv;
                }
            }else{
                if(is_array($mf)){
                    if(count($mf)==3){
                        /**
                         * @var DualResource $dr
                         */
                        $dr=$mf[2]($vv);
                        if($dr!==null) {
                            if ($all) {
                                $modelData[$mf[0]] = $dr->getAll();
                            } else {
                                $modelData[$mf[0]] = $dr->getParameters();
                            }
                        }
                    }elseif(count($mf)==2){
                        if(is_string($mf[1])){
                            /**
                             * @var DualResource $dr
                             */
                            $dr=$mf[1]::fromRequestData($vv);
                            if($all) {
                                $modelData[$mf[0]] = $dr->getAll();
                            }else{
                                $modelData[$mf[0]] = $dr->getParameters();
                            }
                        }else{
                            throw new \Exception('condition is not supported');
                        }
                    }else{
                        //todo all or not (not complete)
                        $modelData[$mf[0]] = $vv;
                    }
                }else{
                    $modelData[$mf]=$vv;
                }
            }
        }

        return $modelData;
    }

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    final public function toArray($request)
    {

        $mapfields=$this->mapFields();
        if(empty($mapfields))
            return parent::toArray($request); // TODO: Change the autogenerated stub

        $result=[];
        foreach ($mapfields as $vf=>$mf){

            if(is_array($mf)){
                if(is_string($mf[1])){
                    $result[$vf] = $mf[1]::collection($this->{$mf[0]});
                }else {
                    $_value=$mf[1]($mf[0]);
                    if($_value!==null)
                        $result[$vf] = $mf[1]($mf[0]);
                }
            }else {
                if(isset($this->{$mf}))
                    $result[$vf] = $this->{$mf};
            }
        }
        return  $result;

    }

    /**
     * Get only mapped data from request plus extra fields
     *
     * @param array $extra
     * @param array $defaults
     * @return mixed
     */
    public function getParameters($extra=[], $defaults=[]){

        $params = $this->generateParametersFromRequest();

        $params=$this->additionalParameters($params, $extra, $defaults);

        return $params;
    }

    /**
     * Get mapped data from request plus others and extra fields
     *
     * @param array $extra
     * @return array
     */
    public function getAll($extra=[], $defaults=[]){

        $params = $this->generateParametersFromRequest(true);

        $params=$this->additionalParameters($params, $extra, $defaults);

        return $params;
    }

    private function addDefaultValues($params, $extraDefaults=[]){
        $extraDefaults=array_merge($this->defaultValues(),$extraDefaults);
        $params=array_merge($extraDefaults,$params);
        return $params;
    }

    /**
     * Adding extra parameters from request to result
     *
     * @param array $params
     * @param array $extra
     * @param array $extraDefaults
     * @return mixed
     */
    private function additionalParameters(array $params,array $extra, $extraDefaults=[]){
        $params=array_merge($params,$extra);

        $params=$this->addDefaultValues($params, $extraDefaults);

        return $params;
    }
}

