        <!-- FOOTER
        --------------------------------->
        <div class="bottom-spacer"></div>
        <htmlpagefooter name="docFooter">
            <div id="footer">
                <table>
                    <tr>
                        <td style="padding: 4px 10px;">
                            <?php
                                echo $this->wpo->get_extra_1();
                            ?>
                        </td>
                        <td style="padding: 4px 10px;">
                            <?php
                                echo $this->wpo->get_extra_2();
                            ?>
                        </td>
                        <td style="padding: 4px 10px;">
                            <?php
                                echo $this->wpo->get_extra_3();
                            ?>
                        </td>
                    </tr>
                </table>
            </div>
        </htmlpagefooter>
    </body>
</html>