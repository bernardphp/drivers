{
  description = "Official Bernard drivers";

  inputs = {
    nixpkgs.url = "nixpkgs/nixos-unstable";
    flake-utils.url = "github:numtide/flake-utils";
  };

  outputs = { self, nixpkgs, flake-utils, ... }:
    flake-utils.lib.eachDefaultSystem (
      system:
        let
          pkgs = import nixpkgs { inherit system; };
          php = (pkgs.php.withExtensions ({ enabled, all }: enabled ++ [ all.xdebug all.redis ]));
        in
          {
            devShells.default = pkgs.mkShell {
              buildInputs = with pkgs;
                [
                  git
                  gnumake
                  # (php.withExtensions ({ enabled, all }: enabled ++ [ all.xdebug all.redis ]))
                  # php.packages.composer
                  php.packages.phpstan
                  php.packages.php-cs-fixer
                  # php.packages.psalm
                ] ++ [ php php.packages.composer ];
            };
          }
    );
}
