<?php

/**
 * Interface WC_Retailcrm_Builder_Interface.
 * Main interface for builders. All builders
 */
interface WC_Retailcrm_Builder_Interface {
    /**
     * Sets data into builder
     *
     * @param array|mixed $data
     *
     * @return self
     */
    public function setData($data);

    /**
     * Returns data present in the builder
     *
     * @return array|mixed
     */
    public function getData();

    /**
     * This method should build result with data present in the builder.
     * It should return builder instance in spite of actual building result.
     * Any exception can be thrown in case of error. It should be processed accordingly.
     *
     * @return self
     * @throws \Exception
     */
    public function build();

    /**
     * This method should reset builder state.
     * In other words, after calling reset() builder inner state should become identical to newly created builder's state.
     *
     * @return self
     */
    public function reset();

    /**
     * Returns builder result. Can be anything (depends on builder).
     *
     * @return mixed|null
     */
    public function getResult();
}
