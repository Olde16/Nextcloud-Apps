<?php

declare(strict_types=1);

script('e621tags', 'admin');
style('e621tags', 'admin');
?>

<div class="section e621tags-admin">
    <div class="e621tags-header">
        <h2>Tagging App for e621 & e6AI</h2>
        <p>
            Configure e621 & e6AI tagging and access.
        </p>
    </div>

    <form
        id="e621tags-admin-form"
        method="post"
        action="<?php p($_['save_url']); ?>"
    >
        <input
            type="hidden"
            name="requesttoken"
            value=""
        >

        <div class="e621tags-grid">

            <section class="e621tags-card e621tags-card-wide">
                <div class="e621tags-card-header">
                    <div>
                        <h3>General Settings</h3>
                        <p>
                            Global settings for tag processing.
                        </p>
                    </div>
                </div>

                <div class="e621tags-setting">
                    <div class="e621tags-setting-text">
                        <strong>Standard Tags</strong>
                        <span>
                            Activate or deactivate all describing
                            Tag-Groups of both APIs.
                        </span>
                    </div>

                    <input
                        type="hidden"
                        name="normalTagsEnabled"
                        value="0"
                    >

                    <label class="e621tags-switch">
                        <input
                            type="checkbox"
                            id="normal_tags_enabled"
                            name="normalTagsEnabled"
                            value="1"
                            <?php if ($_['normal_tags_enabled']) { ?>checked<?php } ?>
                        >
                        <span class="e621tags-switch-slider"></span>
                    </label>
                </div>

                <div class="e621tags-setting">
                    <div class="e621tags-setting-text">
                        <strong>Batch-Size</strong>
                        <span>
                            Maximum number of files processed per scan-job.
                        </span>
                    </div>

                    <input
                        type="number"
                        id="batch_size"
                        name="batchSize"
                        value="<?php p($_['batch_size']); ?>"
                        min="1"
                        max="100"
                        step="1"
                        class="e621tags-number"
                    >
                </div>

                <div class="e621tags-info">
                    <strong>Marker-Tag</strong>
                    <span>
                        „Tagged by e621TagSystem“ is appended to every file that is processed.
                    </span>
                </div>
            </section>

            <!-- E621 -->

            <section class="e621tags-card">
                <div class="e621tags-card-header">
                    <div>
                        <h3>e621</h3>
                        <p>
                            e621 tags and login data.
                        </p>
                    </div>
                </div>

                <div class="e621tags-api-switch">
                    <input
                        type="hidden"
                        name="e621Enabled"
                        value="0"
                    >

                    <label class="e621tags-switch-label">
                        <input
                            type="checkbox"
                            id="e621_enabled"
                            name="e621Enabled"
                            value="1"
                            <?php if ($_['e621_enabled']) { ?>checked<?php } ?>
                        >
                        <span>e621-processing enabled</span>
                    </label>
                </div>

                <div class="e621tags-fields">
                    <label for="e621_username">
                        e621 Username
                    </label>

                    <input
                        type="text"
                        id="e621_username"
                        name="username"
                        value="<?php p($_['username']); ?>"
                        autocomplete="username"
                        class="e621tags-input"
                    >

                    <label for="e621_token">
                        e621 API Token
                    </label>

                    <input
                        type="password"
                        id="e621_token"
                        name="token"
                        value="<?php p($_['token']); ?>"
                        autocomplete="off"
                        class="e621tags-input"
                    >
                </div>

                <div class="e621tags-group">
                    <h4>Tag-Groups</h4>

                    <div class="e621tags-option-list">

                        <div class="e621tags-option">
                            <span>General</span>
                            <input type="hidden" name="e621General" value="0">
                            <label class="e621tags-switch">
                                <input
                                    type="checkbox"
                                    id="e621_general"
                                    name="e621General"
                                    value="1"
                                    <?php if ($_['e621_general']) { ?>checked<?php } ?>
                                >
                                <span class="e621tags-switch-slider"></span>
                            </label>
                        </div>

                        <div class="e621tags-option">
                            <span>Artist</span>
                            <input type="hidden" name="e621Artist" value="0">
                            <label class="e621tags-switch">
                                <input
                                    type="checkbox"
                                    id="e621_artist"
                                    name="e621Artist"
                                    value="1"
                                    <?php if ($_['e621_artist']) { ?>checked<?php } ?>
                                >
                                <span class="e621tags-switch-slider"></span>
                            </label>
                        </div>

                        <div class="e621tags-option">
                            <span>Character</span>
                            <input type="hidden" name="e621Character" value="0">
                            <label class="e621tags-switch">
                                <input
                                    type="checkbox"
                                    id="e621_character"
                                    name="e621Character"
                                    value="1"
                                    <?php if ($_['e621_character']) { ?>checked<?php } ?>
                                >
                                <span class="e621tags-switch-slider"></span>
                            </label>
                        </div>

                        <div class="e621tags-option">
                            <span>Copyright</span>
                            <input type="hidden" name="e621Copyright" value="0">
                            <label class="e621tags-switch">
                                <input
                                    type="checkbox"
                                    id="e621_copyright"
                                    name="e621Copyright"
                                    value="1"
                                    <?php if ($_['e621_copyright']) { ?>checked<?php } ?>
                                >
                                <span class="e621tags-switch-slider"></span>
                            </label>
                        </div>

                        <div class="e621tags-option">
                            <span>Species</span>
                            <input type="hidden" name="e621Species" value="0">
                            <label class="e621tags-switch">
                                <input
                                    type="checkbox"
                                    id="e621_species"
                                    name="e621Species"
                                    value="1"
                                    <?php if ($_['e621_species']) { ?>checked<?php } ?>
                                >
                                <span class="e621tags-switch-slider"></span>
                            </label>
                        </div>

                        <div class="e621tags-option">
                            <span>Invalid</span>
                            <input type="hidden" name="e621Invalid" value="0">
                            <label class="e621tags-switch">
                                <input
                                    type="checkbox"
                                    id="e621_invalid"
                                    name="e621Invalid"
                                    value="1"
                                    <?php if ($_['e621_invalid']) { ?>checked<?php } ?>
                                >
                                <span class="e621tags-switch-slider"></span>
                            </label>
                        </div>

                        <div class="e621tags-option">
                            <span>Lore</span>
                            <input type="hidden" name="e621Lore" value="0">
                            <label class="e621tags-switch">
                                <input
                                    type="checkbox"
                                    id="e621_lore"
                                    name="e621Lore"
                                    value="1"
                                    <?php if ($_['e621_lore']) { ?>checked<?php } ?>
                                >
                                <span class="e621tags-switch-slider"></span>
                            </label>
                        </div>

                        <div class="e621tags-option">
                            <span>Meta</span>
                            <input type="hidden" name="e621Meta" value="0">
                            <label class="e621tags-switch">
                                <input
                                    type="checkbox"
                                    id="e621_meta"
                                    name="e621Meta"
                                    value="1"
                                    <?php if ($_['e621_meta']) { ?>checked<?php } ?>
                                >
                                <span class="e621tags-switch-slider"></span>
                            </label>
                        </div>

                        <div class="e621tags-option e621tags-option-rating">
                            <span>Rating</span>
                            <input type="hidden" name="e621Rating" value="0">
                            <label class="e621tags-switch">
                                <input
                                    type="checkbox"
                                    id="e621_rating"
                                    name="e621Rating"
                                    value="1"
                                    <?php if ($_['e621_rating']) { ?>checked<?php } ?>
                                >
                                <span class="e621tags-switch-slider"></span>
                            </label>
                        </div>

                    </div>
                </div>
            </section>

            <!-- e6AI -->

            <section class="e621tags-card">
                <div class="e621tags-card-header">
                    <div>
                        <h3>e6AI</h3>
                        <p>
                            a6AI tags and login data.
                        </p>
                    </div>
                </div>

                <div class="e621tags-api-switch">
                    <input
                        type="hidden"
                        name="e6aiEnabled"
                        value="0"
                    >

                    <label class="e621tags-switch-label">
                        <input
                            type="checkbox"
                            id="e6ai_enabled"
                            name="e6aiEnabled"
                            value="1"
                            <?php if ($_['e6ai_enabled']) { ?>checked<?php } ?>
                        >
                        <span>e6AI-processing enabled</span>
                    </label>
                </div>

                <div class="e621tags-fields">
                    <label for="e6ai_username">
                        e6AI Username
                    </label>

                    <input
                        type="text"
                        id="e6ai_username"
                        name="e6aiUsername"
                        value="<?php p($_['e6ai_username']); ?>"
                        autocomplete="username"
                        class="e621tags-input"
                    >

                    <label for="e6ai_token">
                        e6AI API Token
                    </label>

                    <input
                        type="password"
                        id="e6ai_token"
                        name="e6aiToken"
                        value="<?php p($_['e6ai_token']); ?>"
                        autocomplete="off"
                        class="e621tags-input"
                    >
                </div>

                <div class="e621tags-group">
                    <h4>Tag-Groups</h4>

                    <div class="e621tags-option-list">

                        <div class="e621tags-option">
                            <span>General</span>
                            <input type="hidden" name="e6aiGeneral" value="0">
                            <label class="e621tags-switch">
                                <input
                                    type="checkbox"
                                    id="e6ai_general"
                                    name="e6aiGeneral"
                                    value="1"
                                    <?php if ($_['e6ai_general']) { ?>checked<?php } ?>
                                >
                                <span class="e621tags-switch-slider"></span>
                            </label>
                        </div>

                        <div class="e621tags-option">
                            <span>Director</span>
                            <input type="hidden" name="e6aiDirector" value="0">
                            <label class="e621tags-switch">
                                <input
                                    type="checkbox"
                                    id="e6ai_director"
                                    name="e6aiDirector"
                                    value="1"
                                    <?php if ($_['e6ai_director']) { ?>checked<?php } ?>
                                >
                                <span class="e621tags-switch-slider"></span>
                            </label>
                        </div>

                        <div class="e621tags-option">
                            <span>Character</span>
                            <input type="hidden" name="e6aiCharacter" value="0">
                            <label class="e621tags-switch">
                                <input
                                    type="checkbox"
                                    id="e6ai_character"
                                    name="e6aiCharacter"
                                    value="1"
                                    <?php if ($_['e6ai_character']) { ?>checked<?php } ?>
                                >
                                <span class="e621tags-switch-slider"></span>
                            </label>
                        </div>

                        <div class="e621tags-option">
                            <span>Copyright</span>
                            <input type="hidden" name="e6aiCopyright" value="0">
                            <label class="e621tags-switch">
                                <input
                                    type="checkbox"
                                    id="e6ai_copyright"
                                    name="e6aiCopyright"
                                    value="1"
                                    <?php if ($_['e6ai_copyright']) { ?>checked<?php } ?>
                                >
                                <span class="e621tags-switch-slider"></span>
                            </label>
                        </div>

                        <div class="e621tags-option">
                            <span>Species</span>
                            <input type="hidden" name="e6aiSpecies" value="0">
                            <label class="e621tags-switch">
                                <input
                                    type="checkbox"
                                    id="e6ai_species"
                                    name="e6aiSpecies"
                                    value="1"
                                    <?php if ($_['e6ai_species']) { ?>checked<?php } ?>
                                >
                                <span class="e621tags-switch-slider"></span>
                            </label>
                        </div>

                        <div class="e621tags-option">
                            <span>Invalid</span>
                            <input type="hidden" name="e6aiInvalid" value="0">
                            <label class="e621tags-switch">
                                <input
                                    type="checkbox"
                                    id="e6ai_invalid"
                                    name="e6aiInvalid"
                                    value="1"
                                    <?php if ($_['e6ai_invalid']) { ?>checked<?php } ?>
                                >
                                <span class="e621tags-switch-slider"></span>
                            </label>
                        </div>

                        <div class="e621tags-option">
                            <span>Lore</span>
                            <input type="hidden" name="e6aiLore" value="0">
                            <label class="e621tags-switch">
                                <input
                                    type="checkbox"
                                    id="e6ai_lore"
                                    name="e6aiLore"
                                    value="1"
                                    <?php if ($_['e6ai_lore']) { ?>checked<?php } ?>
                                >
                                <span class="e621tags-switch-slider"></span>
                            </label>
                        </div>

                        <div class="e621tags-option">
                            <span>Meta</span>
                            <input type="hidden" name="e6aiMeta" value="0">
                            <label class="e621tags-switch">
                                <input
                                    type="checkbox"
                                    id="e6ai_meta"
                                    name="e6aiMeta"
                                    value="1"
                                    <?php if ($_['e6ai_meta']) { ?>checked<?php } ?>
                                >
                                <span class="e621tags-switch-slider"></span>
                            </label>
                        </div>

                        <div class="e621tags-option e621tags-option-rating">
                            <span>Rating</span>
                            <input type="hidden" name="e6aiRating" value="0">
                            <label class="e621tags-switch">
                                <input
                                    type="checkbox"
                                    id="e6ai_rating"
                                    name="e6aiRating"
                                    value="1"
                                    <?php if ($_['e6ai_rating']) { ?>checked<?php } ?>
                                >
                                <span class="e621tags-switch-slider"></span>
                            </label>
                        </div>

                    </div>
                </div>
            </section>
        </div>

        <div class="e621tags-actions">
            <button
                type="submit"
                class="primary"
            >
                Save Settings
            </button>
        </div>
    </form>
</div>
