<?php
namespace core;

/**
 * This class is responsible for validating a data set
 * @author jason
 */
class Validator extends \Exception {

    /** @var bool */
    public $has_error = false;

    /** @var array */
    public $fields = [];

    /** @var array */
    public $errors = [];

    /**
     * Singleton method. Returns uncached instance
     * @return Validator
     */
    public static function init() {
        return new self();
    }

    /**
     * @param $value
     * @param $field
     * @param $title
     * @param bool $required
     * @return null|$value
     */
    private function check_required($value, $field, $title, $required = false) {
        // check if the text is set first
        if (!isset($value) || $value === '') {
            if ($required)
                $this->add_error("$title is required but not set.", $field);

            return null;
        }

        return $value;
    }

    /**
     * Adds an error to the array
     * @param $message
     * @param bool $field [OPTIONAL]
     */
    public function add_error($message, $field = false) {
        if ($field) {
            $this->errors[$field] = $message;

            // set multi-dimensional array value
            $field_keys = explode('.', $field);
            $array_ref  =& $this->fields;

            // explode the $field reference into a multi-dimensional array
            foreach ($field_keys as $field_key) {
                $array_ref[$field_key] = isset($array_ref[$field_key]) ? $array_ref[$field_key] : [];
                $array_ref =& $array_ref[$field_key];
            }

            $array_ref = $message; // finally, set the message.

        } else {
            $this->errors[] = $message;
        }

        $this->has_error = true;
    }

    /**
     * mod10 card validation
     * @param $value
     * @param $field
     * @param $title
     * @param required
     * @return self $this
     */
    public function check_credit_card($value, $field, $title, $required = true) {
        if ($this->check_required($value, $field, $title, $required) === null)
            return null;

        $checksum = 0;
        $length = strlen($value);

        for ($i = (2 - ($length % 2)); $i <= $length; $i += 2) {
            $checksum += intval($value{$i - 1});
        }

        // Analyze odd digits in even length strings or even digits in odd length strings.
        for ($i = ($length % 2) + 1; $i < $length; $i += 2) {
            $digit = intval($value{$i - 1}) * 2;
            $checksum += ($digit < 10 ? $digit : $digit - 9);
        }

        // Check if it's a valid card
        if (!$checksum % 10 == 0)
            $this->add_error("Invalid $title.", $field);

        return $this;
    }

    /**
     * @param $value
     * @param $field
     * @param $title
     * @param int $min
     * @param int $max
     * @param bool $required
     * @return self $this
     */
    public function check_text($value, $field, $title, $min, $max, $required = true) {
        if ($this->check_required($value, $field, $title, $required) === null)
            return null;

        $length = mb_strlen($value);
        if ($length < $min || $length > $max)
            $this->add_error("Invalid $title. Must be between $min and $max characters", $field);

        return $this;
    }

    /**
     * @param $value
     * @param $field
     * @param $title
     * @param bool $required
     * @return self $this
     */
    public function check_phone($value, $field, $title, $required = true) {
        if ($this->check_required($value, $field, $title, $required) === null)
            return null;

        $raw_number  = preg_replace('/[^\d]+/', '', $value);
        $digit_count = strlen($raw_number);

        $fail = $digit_count > 20; // accept larger numbers to accomodate extensions
        $fail = $fail || $digit_count < 10; // phone numbers must be at least 10 characters
        $fail = $fail || is_float($value) || is_double($value); // prevent ridiculously large numbers (1.0E+ syntax)

        if ($fail)
            $this->add_error("Invalid $title.", $field);

        return $this;
    }

    /**
     * @param $value
     * @param $field
     * @param $title
     * @param bool $required
     * @return self $this
     */
    public function check_email($value, $field, $title, $required = true) {
        if ($this->check_required($value, $field, $title, $required) === null)
            return null;

        // Check if it's a valid email
        if (!filter_var($value, FILTER_VALIDATE_EMAIL))
            $this->add_error("Invalid $title.", $field);

        return $this;
    }

    /**
     * @param $value
     * @param $field
     * @param $title
     * @param bool $required
     * @return self $this
     */
    public function check_number($value, $field, $title, $required = true) {
        if ($this->check_required($value, $field, $title, $required) === null)
            return null;

        if (!is_numeric($value))
            $this->add_error("Invalid $title. Must be a Number.", $field);

        return $this;
    }

    /**
     * @param $value
     * @param $field
     * @param $title
     * @param bool $required
     * @return self $this
     */
    public function check_url($value, $field, $title, $required = true) {
        if ($this->check_required($value, $field, $title, $required) === null)
            return null;

        if (!filter_var($value, FILTER_VALIDATE_URL))
            $this->add_error("Invalid $title.", $field);

        return $this;
    }

    /**
     * @param $value
     * @param $field
     * @param $title
     * @param $options
     * @param bool $required
     * @return self $this
     */
    public function check_list($value, $field, $title, $options = [], $required = true) {
        if ($this->check_required($value, $field, $title, $required) === null)
            return null;

        // Check if value exists in the options
        if (!in_array($value, $options))
            $this->add_error("Invalid $title.", $field);

        return $this;
    }

    /**
     * @param $value
     * @param $field
     * @param $title
     * @param bool $required
     * @return self $this
     */
    public function check_date($value, $field, $title, $required = true) {
        if ($this->check_required($value, $field, $title, $required) === null)
            return null;

        $valid = strtotime($value) !== false;

        if (!$valid)
            $this->add_error("Invalid $title.", $field);

        return $this;
    }

    /**
     * @param $value
     * @param $field
     * @param $title
     * @param bool $required
     * @return self $this
     */
    public function check_timestamp($value, $field, $title, $required = true) {
        if ($this->check_required($value, $field, $title, $required) === null)
            return null;

        $valid = is_numeric($value) && intval($value) == strval($value);

        if (!$valid)
            $this->add_error("Invalid $title.", $field);

        return $this;
    }

    /**
     * Function to validate all input
     * @throws self
     */
    public function done($glue = "\n") {
        if ($this->has_error) {
            $this->message = implode($this->errors, $glue);
            throw $this;
        }
    }
}
