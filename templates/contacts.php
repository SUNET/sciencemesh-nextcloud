<?php
script('sciencemesh', 'contacts');
style('sciencemesh', 'style');
script("sciencemesh", "vendor/simplyedit/simply-edit");
script("sciencemesh", "vendor/simplyedit/simply.everything");
?>
<div id="app">
	<div id="app-navigation">
		<?php print_unescaped($this->inc('navigation/index')); ?>
	</div>
	<div id="app-content">
		<div id="app-content-wrapper" class="viewcontainer">
				<div class="app-content-list">
					<div href="#" class="app-content-list-item" id="test">
						<!-- div class="app-content-list-item-star icon-starred"></div -->
						<div class="app-content-list-item-icon" style="background-color: rgb(151, 72, 96);">M</div>
						<div class="app-content-list-item-line-one" id="show_result"></div>
						<div class="icon-delete"></div>
					</div>
					<div href="#" class="app-content-list-item" id="elem">
						<div class="app-content-list-item-icon" style="background-color: rgb(31, 72, 96);">+</div>
						<div class="app-content-list-item-line-one">Generate a new token</div>
						<div class="app-content-list-item-line-two">Tokens are valid for 24 hours</div>
						<div class="app-content-list-item-menu">
							<div class="icon-add"></div>
						</div>
					</div>
					<div id="test" href="#" class="app-content-list-item">
						<!-- div class="app-content-list-item-star icon-starred"></div -->
						<div class="app-content-list-item-icon" style="background-color: rgb(151, 72, 96);"></div>
						<div class="app-content-list-item-line-one" id="show_result"></div>
						<div class="app-content-list-item-line-two" id="provider"></div>
						<div class="app-content-list-item-menu">
							<div class="icon-clippy"></div>
						</div>
						<span class="app-content-list-item-details"></span>
						<!--<div class="app-content-list-item-line-two">Copy to clipboard</div>-->
					</div>
				</div>

				<div class="app-content-detail">
					<div class="section">
						<!--<p>To start collaborating with someone on ScienceMesh, enter the received invitation token here.</p>
						<p>To send an invitation, generate a token in 'Invitations' and send it to them.</p>-->
						<p id="test_error"></p>
						<p id="display_name"></p>
						<p id="provider"></p>
					</div>
				</div>
		</div>
	</div>
</div>
