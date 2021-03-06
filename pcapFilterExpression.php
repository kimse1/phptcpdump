<?php

/**
 * Pcap filter expression generator
 * Generate tcpdump pcap-filter expressions
 * Support: host, port and portrange primitives
 *
 * @author Kim Henriksen <kh@ipimp.at>
 * @see http://www.manpagez.com/man/7/pcap-filter/
 * @license http://philsturgeon.co.uk/code/dbad-license DBAD License
 */
class pcapFilterExpression {

    /**
     * Expression holder
     * 
     * @var string 
     */
    private $expression = "";

    /**
     * pcap filter expression stack
     * 
     * @var array 
     */
    private $expression_stack = array();
   
    /**
     * Init
     * initialize the class 
     */
    public function init() {
        $this->expression_stack = array();
        $this->expression = "";
    }

    /**
     * Generate a host primitive
     * 
     * @param string $ip
     * @param string $direction optional packet direction 'src' or 'dst' or emptry string
     * @return \pcapFilterExpression
     * @throws InvalidArgumentException 
     */
    public function host($ip, $direction = '') {

        // Validate packet direction
        if (!$this->isValidDirection($direction))
            throw new InvalidArgumentException(sprintf("expression error: invalid packet direct flow %s, must be 'src', 'dst' or none (any)", $direction));

        // Validate ip
        if (!$this->isValidIP($ip))
            throw new InvalidArgumentException(sprintf("expression error: %s is not a valid ip", $ip));

        // Parse primitive
        $this->expression .= trim(sprintf("%s host %s", $direction, $ip));
        return $this;
    }

    /**
     * Generate a port primitive
     * 
     * @param int $port
     * @param string $direction optional packet direction src, dst
     * @param string $direction optional protocol tcp or udp
     * @return \pcapFilterExpression
     * @throws InvalidArgumentException 
     */
    public function port($port, $direction = '', $protocol = '') {

        // Validate packet direction
        if (!$this->isValidDirection($direction))
            throw new InvalidArgumentException(sprintf("expression error: invalid packet direct flow %s", $direction));

        // Validate protocol
        if (!$this->isValidProtocol($protocol))
            throw new InvalidArgumentException(sprintf("expression error: invalid protocol %s", $protocol));

        // Validate port number
        if (!$this->isValidPortNumber($port))
            throw new InvalidArgumentException(sprintf("expression error: %s doesn't look like a valid port number", $port));

        // Type cast port to an integer
        $port = (int) $port;

        // Parse primitive
        $this->expression .= trim(sprintf("%s %s port %d", $protocol, $direction, $port));
        return $this;
    }

    /**
     * Generate a port range primitive
     * 
     * @param int $start_port
     * @param iint $end_port
     * @param string $direction optional packet direction 'src', 'dst' or emptry string
     * @param string $direction optional protocol 'tcp' or 'udp' or emptry string
     * @return \pcapFilterExpression
     * @throws InvalidArgumentException 
     */
    public function port_range($start_port, $end_port, $direction = '', $protocol = '') {

        // Validate packet direction
        if (!$this->isValidDirection($direction))
            throw new InvalidArgumentException(sprintf("expression error: invalid packet direct flow %s", $direction));

        // Validate protocol
        if (!$this->isValidProtocol($protocol))
            throw new InvalidArgumentException(sprintf("expression error: invalid protocol %s", $protocol));

        // Validate port number
        if (!$this->isValidPortNumber($start_port) OR !$this->isValidPortNumber($end_port))
            throw new InvalidArgumentException(sprintf("expression error: %s-%s doesn't look like a valid port range", $start_port, $end_port));

        // Type cast ports
        $start_port = (int) $start_port;
        $end_port = (int) $end_port;

        // Parse primitive
        $this->expression .= trim(sprintf("%s %s portrange %s-%s", $protocol, $direction, $start_port, $end_port));
        return $this;
    }

    /**
     * Append a concatenation operator
     * 
     * @return \pcapFilterExpression 
     */
    public function concate() {
        $this->expression .= " and ";
        return $this;
    }

    /**
     * Append a alternation operator
     * 
     * @return \pcapFilterExpression 
     */
    public function alternate() {
        $this->expression .= " or ";
        return $this;
    }

    /**
     * Append a negation operator
     * 
     * @return \pcapFilterExpression 
     */
    public function negate() {
        $this->expression .= " not ";
        return $this;
    }

    /**
     * Group primitives
     * parenthesize previous primitives and operators
     * also stacks it, so no need to call end()
     * 
     * 
     * @param string $operator prepend a operator to the expression
     * can be operators are: 
     *  concate: (concate, and, &&)
     *  alternate: (alternate, or, ||)
     *  negate: (negate, not, !)
     * @return \pcapFilterExpression
     * @throws UnexpectedValueException 
     */
    public function group($operator = NULL) {
        if (!empty($this->expression)) {
            // Enclose primitives in parenthesize
            $this->expression = "(" . $this->expression . ")";

            switch ($operator) {
                case "alternate":
                case "or":
                case "||":
                    $this->expression = ' or ' . $this->expression;
                    break;
                case "negate":
                case "not":
                case "!":
                    $this->expression = ' not ' . $this->expression;
                    break;
                case "concate":
                case "and":
                case "&&":
                    $this->expression = ' and ' . $this->expression;
                    break;
                default :
                    break;
            }

            $this->end();
            $this->begin();
        } else {
            throw new UnexpectedValueException('expression is empty');
        }
        return $this;
    }

    /**
     * Begin
     * start a new expression
     * 
     * @return \pcapFilterExpression 
     */
    public function begin() {
        $this->expression = "";
        return $this;
    }

    /**
     * End
     * end expression and stack it
     * 
     * @return \pcapFilterExpression 
     */
    public function end() {
        $this->stack();
        return $this;
    }

    /**
     * Add it to the expression stack
     * 
     * @return \pcapFilterExpression
     * @throws UnexpectedValueException 
     */
    private function stack() {
        // add expr to stack
        if (!empty($this->expression))
            $this->expression_stack[] = $this->expression;
        else
            throw new UnexpectedValueException("Expression empty, not stacking");

        return $this;
    }

    /**
     * Get pcap filter expression string
     * Returns the pcap filter expression stack array as string
     * 
     * @return string 
     */
    public function getPcapFilterExpressionString() {
        // implode expression stack
        $expr_str = "";
        foreach ($this->expression_stack as $expr) {
            // trim whitespaces
            $expr_str = trim($expr_str .= " " . trim($expr));
        }
        return $expr_str;
    }

    /**
     * Get pcap fitler expression stack
     * Returns the pcap filter expression stack array
     * 
     * @return array 
     */
    public function getPcapFilterStack() {
        return $this->expression_stack;
    }

    /**
     * Validate ip
     * 
     * @param string $ip
     * @return boolean 
     */
    private function isValidIP($ip) {
        return filter_var($ip, FILTER_VALIDATE_IP);
    }

    /**
     * Validate packet direction
     * 
     * @param string $dest
     * @return boolean 
     */
    private function isValidDirection($dest) {
        return ($dest != 'dst' OR $dest != 'src' OR $dest == '');
    }

    /**
     * Validate protocol
     * 
     * @param string $protocol
     * @return boolean 
     */
    private function isValidProtocol($protocol) {
        return ($protocol != 'tcp' OR $protocol != 'udp' OR $protocol == '');
    }

    /**
     * Validate port number
     * 
     * @param int $port
     * @return boolean 
     */
    private function isValidPortNumber($port) {
        if (preg_match("/^[0-9]+$/", $port)) {
            $port = (int) $port;
            return ($port >= 0 || $port <= 65535);
        }
        else
            return false;
    }

}

?>
