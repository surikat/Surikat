<?php
namespace surikat\view\CssSelector\Parser;
use ArrayObject;
use Closure;
use surikat\view\CssSelector\Arr\Arr;
use surikat\view\CssSelector\Parser\Combinator\CssParserCombinator;
use surikat\view\CssSelector\Parser\Combinator\CssParserCombinatorFactory;
use surikat\view\CssSelector\Parser\Filter\CssParserFilter;
use surikat\view\CssSelector\Parser\Filter\CssParserFilterAttr;
use surikat\view\CssSelector\Parser\Filter\CssParserFilterClass;
use surikat\view\CssSelector\Parser\Filter\CssParserFilterId;
use surikat\view\CssSelector\Parser\Filter\CssParserFilterPseudo;
use surikat\view\CssSelector\Parser\Filter\CssParserFilterPseudoFactory;
use surikat\view\CssSelector\Parser\Model\CssParserModelElement;
use surikat\view\CssSelector\Parser\Model\CssParserModelFactor;
use surikat\view\CssSelector\Parser\Model\CssParserModelSelector;
use surikat\view\CssSelector\Text\Parser\Exception\TextParserException;
use surikat\view\CssSelector\Text\Parser\TextParser;
 
/**
 * Class CssParser.
 * 
 * <p>This class parses and evaluates a CSS expression.</p>
 * 
 * <pre>
 * selectorList              = selector {"," selector}
 * selector                  = factor {factor}
 * factor                    = combinator element | element
 * element                   = ("*" | identifier) {filter}
 * filter                    = class-filter | id-filter | attr-filter | pseudo-filter
 * class-filter              = "." identifier
 * id-filter                 = "#" identifier
 * attr-filter               = "[" identifier [attr-operator value] "]"
 * pseudo-filter             = ":" ( pseudo-first-child-filter
 *                             | pseudo-nth-child-filter )
 * pseudo-nth-child-filter   = "nth-child" "(" number ")"
 * pseudo-first-child-filter = "first-child"
 * identifier                = ( "_" | alphanum ) { "_" | "-" | alphanum }
 * attr-operator             = "=" | "~="
 * combinator                = ">" | "+" | "~"
 * value                     = quoted-string | alphanum {alphanum}
 * </pre>
 * 
 * @category XML_CSS
 * @package  XML_CSS_CssSelector
 * @author   Gonzalo Chumillas <gonzalo@soloproyectos.com>
 * @license  https://raw2.github.com/soloproyectos/php.common-libs/master/LICENSE BSD 2-Clause License
 * @link     https://github.com/soloproyectos/php.common-libs
 */
class CssParser extends TextParser{
    const IDENTIFIER = "[_a-z0-9][_\-a-z0-9]*";
    private $_node;
    private $_pseudoFilters;
    private $_combinators;
    public function __construct($target, $charset = "", $mimetype = "")
    {
        $this->_pseudoFilters = [];
        $this->_combinators = [];
        
        $this->_node = $target;
        
        // registers pseudo filters
        $this->registerPseudoFilter("first", "CssParserFilterPseudoFirst");
        $this->registerPseudoFilter("last", "CssParserFilterPseudoLast");
        $this->registerPseudoFilter("eq", "CssParserFilterPseudoEq");
        $this->registerPseudoFilter("nth", "CssParserFilterPseudoEq");
        $this->registerPseudoFilter("even", "CssParserFilterPseudoEven");
        $this->registerPseudoFilter("odd", "CssParserFilterPseudoOdd");
        $this->registerPseudoFilter("lt", "CssParserFilterPseudoLt");
        $this->registerPseudoFilter("gt", "CssParserFilterPseudoGt");
        $this->registerPseudoFilter("nth-child", "CssParserFilterPseudoNthChild");
        $this->registerPseudoFilter(
            "not", "CssParserFilterPseudoNot", "selectorList"
        );
        $this->registerPseudoFilter( //by surikat
            "has", "CssParserFilterPseudoHas", "selectorList"
        );
        $this->registerPseudoFilter( //by surikat
            "hasnt", "CssParserFilterPseudoHasnt", "selectorList"
        );
        $this->registerPseudoFilter(
            "first-child", "CssParserFilterPseudoFirstChild"
        );
        
        // registers combinators
        $this->registerCombinator("", "CssParserCombinatorDescendant");
        $this->registerCombinator(">", "CssParserCombinatorChild");
        $this->registerCombinator("+", "CssParserCombinatorAdjacent");
        $this->registerCombinator("~", "CssParserCombinatorGeneral");
        
        parent::__construct("");
    }
    
    public function query($selectorList){
        return $this->parse($selectorList);
    }
    
    /**
     * Registers a new user defined pseudo-filter.
     * 
     * <p>Example 1:</p>
     * <pre>
     * // is the node in penultimate position?
     * $selector->registerPseudoFilter(
     *     "penultimate", function ($node, $input, $position, $items
     * ) {
     *     return $position == count($items) - 2;
     * });
     * $items = $selector->query('item:penultimate');
     * </pre>
     * 
     * <p>Example 2:</p>
     * <pre>
     * // is node position a Fibonacci number?
     * $css->registerPseudoFilter(
     *     "fibonacci", function ($node, $input, $position, $items
     * ) {
     *     $is_fibonacci = false;
     *     if ($position > 0) {
     *         $n = sqrt(5 * pow($position, 2) + 4);
     *         $is_fibonacci = $n - floor($n) == 0;
     *         if (!$is_fibonacci) {
     *             $n = sqrt(5 * pow($position, 2) - 4);
     *             $is_fibonacci = $n - floor($n) == 0;
     *         }
     *     }
     *     return $is_fibonacci;
     * });
     * $items = $selector->query('item:fibonacci');
     * </pre>
     * 
     * <p>Example 3:</p>
     * <pre>
     * // is the node position divisible by a given number?
     * $css->registerPseudoFilter(
     *     "divisible", function ($node, $input, $position, $items
     * ) {
     *     $n = intval($input);
     *     return $n > 0 && $position % $n == 0;
     * });
     * 
     * // selects all nodes 'divisible' by 3
     * $items = $selector->query('item:divisible(3)');
     * </pre>
     * 
     * @param string         $name   Pseudo-filter name
     * @param string|Closure $object Class name or user defined function.
     * @param string         $entity Entity (default is 'value')
     * 
     * @return void
     */
    public function registerPseudoFilter($name, $object, $entity = "value"){
        if (is_callable($object))
            // user defined pseudo-filter
            $this->_pseudoFilters[$name] = [
                "classname" => "CssParserFilterPseudoUserDefined",
                "user_def_function" => $object,
                "entity" => $entity
            ];
        else
            $this->_pseudoFilters[$name] = [
                "classname" => $object,
                "user_def_function" => null,
                "entity" => $entity
            ];
    }
    public function registerCombinator($name, $object){
        if (is_callable($object))
            $this->_combinators[$name] = [
                "classname" => "CssParserCombinatorUserDefined",
                "user_def_function" => $object
            ];
        else
            $this->_combinators[$name] = [
                "classname" => $object,
                "user_def_function" => null
            ];
    }
    protected function combinator(){
        $ret = false;
        $combinatorNames = array_keys($this->_combinators);
        
        if (list($name) = $this->in($combinatorNames)) {
            $combinator = $this->_combinators[$name];
            $ret = CssParserCombinatorFactory::getInstance(
                $combinator["classname"], $combinator["user_def_function"]
            );
        }
        return $ret;
    }
    protected function attrOperator(){
        return $this->in(CssParserFilterAttr::getOperators());
    }
    protected function identifier(){
        if (list($id) = $this->match(CssParser::IDENTIFIER))
            return [$id];
        return false;
    }    
    protected function value(){
		/** ex:
		 * 'hello'
		 * "hello\"man"
		 * 'hello\'man'
		 * 0015blah
		 * _blah_
		 */
        if (   !(list($value) = $this->str())
            && !(list($value) = $this->number())
            && !(list($value) = $this->match(CssParser::IDENTIFIER))
        )
            return false;
        return [$value];
    }
    protected function pseudoFilter(){
        if (!$this->match("/^\:/"))
            return false;
        if (!list($name) = $this->is("identifier"))
            throw new TextParserException("Invalid identifier", $this);
        $filter = Arr::get($this->_pseudoFilters, $name, null);
        if ($filter === null)
            throw new TextParserException("Unknown pseudo-filter", $this);
        $input = "";
        if ($this->eq("(")){
            if (!$input = $this->is($filter["entity"]))
                throw new TextParserException("Invalid input", $this);
            if (is_array($input))
                $input = $input[0];
            if (!$this->eq(")"))
                throw new TextParserException("Invalid expression", $this);
        }
        $pseudoFilter = CssParserFilterPseudoFactory::getInstance(
            $filter["classname"], $input, $filter["user_def_function"]
        );
        return $pseudoFilter;
    }
    protected function attrFilter(){
        $attrName = "";
        $op = "";
        $value = "";
        if (!$this->match("/^\[/"))
            return false;
        if (!list($attrName) = $this->is("identifier"))
            throw new TextParserException("Invalid identifier", $this);
        if (list($op) = $this->is("attrOperator")) {
            if (!list($value) = $this->is("value"))
                throw new TextParserException("Invalid attribute operator", $this);
        }
        if (!$this->eq("]"))
            throw new TextParserException("Invalid expression", $this);
        return new CssParserFilterAttr($attrName, $op, $value);
    }
    protected function idFilter(){
        $id = "";
        if (!$this->match("/^\#/"))
            return false;
        if (!list($id) = $this->is("identifier"))
            throw new TextParserException("Invalid identifier", $this);
        return new CssParserFilterId($id);
    }
    protected function classFilter(){
        $className = "";
        if (!$this->match("/^\./"))
            return false;
        if (!list($className) = $this->is("identifier"))
            throw new TextParserException("Invalid identifier", $this);
        return new CssParserFilterClass($className);
    }
    protected function filter(){
        $filter = null;
        
        if (   (!$filter = $this->is("classFilter"))
            && (!$filter = $this->is("idFilter"))
            && (!$filter = $this->is("attrFilter"))
            && (!$filter = $this->is("pseudoFilter"))
        ) {
            return false;
        }
        return $filter;
    }
    protected function element(){
        $element = null;
        $filter = null;
        $tagName = "*";
        $this->match("\s+");
        if ((list($name) = $this->eq("*"))||(list($name) = $this->is("identifier")))
            $tagName = $name? $name : "*";
        elseif (!$filter = $this->is("filter"))
            return false;
        $element = new CssParserModelElement($tagName);
        if($filter)
            $element->addFilter($filter);
        while($filter = $this->is("filter"))
            $element->addFilter($filter);
        return $element;
    }
    protected function factor(){
        $combinator = null;
        if ($combinator = $this->is("combinator")) {
            if (!$element = $this->is("element"))
                throw new TextParserException("Invalid expression", $this);
        }
        elseif ($element = $this->is("element"))
            $combinator = CssParserCombinatorFactory::getInstance("CssParserCombinatorDescendant");
        else
            return false;
        return new CssParserModelFactor($combinator, $element);
    }
    protected function selector(){
        $factor = null;
        if (!$factor = $this->is("factor"))
            return false;
        $selector = new CssParserModelSelector();
        $selector->addFactor($factor);
        while ($factor = $this->is("factor"))
            $selector->addFactor($factor);
        return $selector;
    }
    protected function selectorList(){
        $nodes = [];
        do {
            if (!$selector = $this->is("selector"))
                break;
            $nodes = array_merge(
                $nodes,
                $selector->filter($this->_node)
            );
        } while ($this->eq(","));
        return new ArrayObject(array_unique($nodes));
    }
    protected function _parse(){
        return $this->is("selectorList");
    }
}