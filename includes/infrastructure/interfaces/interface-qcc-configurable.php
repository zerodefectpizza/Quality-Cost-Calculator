<?php
interface QCC_Configurable {
    public function configure(array $config);
    public function get_configuration();
}