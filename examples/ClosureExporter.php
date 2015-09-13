<?php
use SuperClosure\Serializer;
use SuperClosure\Analyzer\AstAnalyzer as DefaultAnalyzer;
use SuperClosure\Analyzer\ClosureAnalyzer;
use SuperClosure\Exception\ClosureUnserializationException;

/**
 * This is the serializer class used for serializing Closure objects.
 *
 * We're abstracting away all the details, impossibilities, and scary things
 * that happen within.
 */
class ClosureExporter
{

    /**
     * The special value marking a recursive reference to a closure.
     *
     * @var string
     */
    const RECURSION = "{{RECURSION}}";
    const REFERENCE = "{{REFERENCE}}";
    /**
     * The keys of closure data required for serialization.
     *
     * @var array
     */
    private static $dataToKeep = [
        'code'     => true,
        'context'  => true,
        'binding'  => true,
        'scope'    => true,
        'isStatic' => true,
    ];

    /**
     * The closure analyzer instance.
     *
     * @var ClosureAnalyzer
     */
    private $analyzer;

    /**
     * The HMAC key to sign serialized closures.
     *
     * @var string
     */
    private $signingKey;
      
    private $data;
    
    private $closure;
    
    /**
     * Create a new serializer instance.
     *
     * @param ClosureAnalyzer|null $analyzer   Closure analyzer instance.
     * @param string|null          $signingKey HMAC key to sign closure data.
     */
    public function __construct(
        ClosureAnalyzer $analyzer = null,
        $signingKey = null
    ) {
        $this->analyzer = $analyzer ?: new DefaultAnalyzer;
        $this->signingKey = $signingKey;
    }
    
    public function setContextReferences($refs)
    {
        $this->refs = $refs;
    }
    
    /**
     * @inheritDoc
     */
    public function export(\Closure $closure)
    {
        $temp = $this->getData($closure, true);
        return var_export($temp, true);
    }    
    
    public function import($data, &$ref)
    {
        $this->data = eval('return ' . $data . ';');
        $this->reconstructRef();
        return $this->getClosure();
    }
    
    public function reconstructRef(&$ref) {
        foreach($this->data['context'] as $key => &$value) {
            if ($value == self::REFERENCE) {
                $value = $this->refs[$key];
            }
        }
        
        $this->reconstructClosure();
        
        if (!$this->closure instanceof \Closure) {
            throw new ClosureUnserializationException(
                'The closure is corrupted and cannot be unserialized.'
            );
        }

        // Rebind the closure to its former binding, if it's not static.
        if (!$this->data['isStatic']) {
            $this->closure = $this->closure->bindTo(
                $this->data['binding'],
                $this->data['scope']
            );
        }        
    }
    
    /**
     * Reconstruct the closure.
     *
     * HERE BE DRAGONS!
     *
     * The infamous `eval()` is used in this method, along with `extract()`,
     * the error suppression operator, and variable variables (i.e., double
     * dollar signs) to perform the unserialization work. I'm sorry, world!
     */
    private function reconstructClosure()
    {
        // Simulate the original context the closure was created in.
        extract($this->data['context'], EXTR_OVERWRITE);

        // Evaluate the code to recreate the closure.
        if ($_fn = array_search(Serializer::RECURSION, $this->data['context'], true)) {
            @eval("\${$_fn} = {$this->data['code']};");
            $this->closure = $$_fn;
        } else {
            @eval("\$this->closure = {$this->data['code']};");
        }
    }     
    
    /**
     * @inheritDoc
     */
    public function getData(\Closure $closure, $forSerialization = false)
    {
        // Use the closure analyzer to get data about the closure.
        $data = $this->analyzer->analyze($closure);

        // If the closure data is getting retrieved solely for the purpose of
        // serializing the closure, then make some modifications to the data.
        if ($forSerialization) {
            // If there is no reference to the binding, don't serialize it.
            if (!$data['hasThis']) {
                $data['binding'] = null;
            }

            // Remove data about the closure that does not get serialized.
            $data = array_intersect_key($data, self::$dataToKeep);

            // Wrap any other closures within the context.
            foreach ($data['context'] as &$value) {
                if ($value instanceof \Closure) {
                    $value = ($value === $closure)
                        ? self::RECURSION
                        : new SerializableClosure($value, $this);
                }
            }
            // test
            foreach ($data['context'] as $key => &$value) {
                if (isset($this->refs[$key])) {
                    $value = self::REFERENCE;
                }
            }            
        }

        return $data;
    }    

    /**
     * Return the original closure object.
     *
     * @return \Closure
     */
    public function getClosure()
    {
        return $this->closure;
    }    
}
