<?php
interface QCC_Renderable {
    public function render($data = array());
    public function can_render($data = array());
}